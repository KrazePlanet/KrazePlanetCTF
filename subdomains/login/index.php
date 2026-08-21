<?php
session_start();

// ── Database Configuration ──────────────────────────────────────────────────
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "KrazePlanet_DB";

// Establish database connection
$conn = @mysqli_connect($db_host, $db_user, $db_pass);
if (!$conn) {
    die("<div style='font-family:sans-serif;padding:30px;background:#f8d7da;color:#721c24;margin:50px auto;max-width:600px;border-radius:8px;'><h3>Database Connection Error</h3><p>Could not connect to MySQL server. Please ensure XAMPP/LAMPP MySQL is running.</p><p><code>" . htmlspecialchars(mysqli_connect_error()) . "</code></p></div>");
}

@mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $db_name");
@mysqli_select_db($conn, $db_name);

// ── Schema Initialization ───────────────────────────────────────────────────
function setup_moviexchange_schema($conn) {
    // 1. Users table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS mx_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'partner',
        company VARCHAR(150) DEFAULT 'Indie Cinema Ltd',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed default users if empty
    $chk = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM mx_users");
    if ($chk && ($row = mysqli_fetch_assoc($chk)) && $row['c'] == 0) {
        $adm_pass = password_hash('MasterCinema2026!', PASSWORD_DEFAULT);
        $usr_pass = password_hash('operator123', PASSWORD_DEFAULT);
        @mysqli_query($conn, "INSERT INTO mx_users (full_name, email, password, role, company) VALUES 
            ('Administrator', 'admin@moviexchange.com', '$adm_pass', 'admin', 'movieXchange Global Ops'),
            ('Marcus Vance', 'marcus.vance@apexcinemas.com', '$usr_pass', 'exhibitor', 'Apex Theatres Group'),
            ('Elena Rostova', 'elena@novafilms.org', '$usr_pass', 'distributor', 'Nova Film Distribution')");
    }

    // 2. Account Applications table (Used for Application Tracking SQLi)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS mx_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ref_no VARCHAR(50) NOT NULL UNIQUE,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        company VARCHAR(150) NOT NULL,
        company_type VARCHAR(100) NOT NULL,
        country VARCHAR(100) NOT NULL,
        cinema_site VARCHAR(150) NOT NULL,
        pos_operator VARCHAR(100) NOT NULL,
        phone VARCHAR(50) NOT NULL,
        email VARCHAR(150) NOT NULL,
        details TEXT,
        status VARCHAR(50) DEFAULT 'Under Review',
        internal_notes TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $chk_apps = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM mx_applications");
    if ($chk_apps && ($row = mysqli_fetch_assoc($chk_apps)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO mx_applications (ref_no, first_name, last_name, company, company_type, country, cinema_site, pos_operator, phone, email, details, status, internal_notes) VALUES
            ('APP-2026-8821', 'David', 'Kensington', 'Starlight Multiplexes', 'Cinema Chain', 'United States', 'Starlight Grand NYC (Screen 1-16)', 'Vista Cinema', '+1 (212) 555-0199', 'dkensington@starlight.com', 'Requesting API key for DCP automated ingestion and KDM distribution.', 'Approved', 'Verified Cinema chain. API Access granted.'),
            ('APP-2026-9042', 'Sophia', 'Martins', 'Aurora Picturehouse', 'Exhibitor', 'United Kingdom', 'Aurora West End (Screen 1-4)', 'Veezi', '+44 20 7946 0912', 'sophia@aurorapictures.co.uk', 'Integration with movieXchange ticketing sync.', 'Under Review', 'Pending background identity check and POS compliance test.'),
            ('APP-2026-9315', 'Karthik', 'Raman', 'Zenith Studios', 'Production House', 'India', 'Zenith Mumbai Preview Theatre', 'Agile Ticketing', '+91 98200 12345', 'karthik@zenithstudios.in', 'Direct DCP upload pipeline setup.', 'Pending Documents', 'Awaiting GST registration document upload.')");
    }

    // 3. Film Catalog table (Used for ORDER BY and Filter SQLi)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS mx_films (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        distributor VARCHAR(150) NOT NULL,
        dcp_format VARCHAR(50) NOT NULL,
        audio_channels VARCHAR(50) NOT NULL,
        aspect_ratio VARCHAR(50) NOT NULL,
        duration_mins INT NOT NULL,
        release_date DATE NOT NULL,
        kdm_status VARCHAR(50) NOT NULL
    )");

    $chk_films = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM mx_films");
    if ($chk_films && ($row = mysqli_fetch_assoc($chk_films)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO mx_films (title, distributor, dcp_format, audio_channels, aspect_ratio, duration_mins, release_date, kdm_status) VALUES
            ('Chronicles of Orion: Resurgence', 'Apex International', 'SMPTE 4K DCP', 'Dolby Atmos 7.1', '2.39:1 Scope', 148, '2026-09-12', 'Active KDM'),
            ('Neon Mirage', 'Nova Film Distribution', 'Interop 2K DCP', '5.1 Surround', '1.85:1 Flat', 114, '2026-08-25', 'Active KDM'),
            ('Echoes from the Silent Abyss', 'Solaris Global Media', 'SMPTE 4K HDR', 'Dolby Atmos 9.1.4', '2.39:1 Scope', 136, '2026-10-05', 'Pending Window'),
            ('Midnight Symphony in Vienna', 'EuroScreen Pictures', 'Interop 2K DCP', '7.1 Surround', '1.85:1 Flat', 102, '2026-08-01', 'Archived'),
            ('Quantum Vanguard', 'Zenith Studios', 'SMPTE 4K High Frame Rate', 'Dolby Atmos', '2.39:1 Scope', 155, '2026-11-20', 'Upcoming')");
    }

    // 4. Cinema POS Terminals table (Used for Integer Parameter SQLi)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS mx_terminals (
        terminal_id INT AUTO_INCREMENT PRIMARY KEY,
        site_id INT NOT NULL,
        terminal_name VARCHAR(100) NOT NULL,
        ip_address VARCHAR(50) NOT NULL,
        pos_software VARCHAR(100) NOT NULL,
        status VARCHAR(50) NOT NULL,
        kdm_endpoint VARCHAR(255) NOT NULL
    )");

    $chk_term = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM mx_terminals");
    if ($chk_term && ($row = mysqli_fetch_assoc($chk_term)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO mx_terminals (site_id, terminal_name, ip_address, pos_software, status, kdm_endpoint) VALUES
            (101, 'Box Office Primary #1', '10.24.110.12', 'Vista Cinema Server v5.4', 'ONLINE', 'https://kdm-hub.starlight.com/api/v1/ingest'),
            (101, 'Concessions POS #3', '10.24.110.15', 'Vista Client v5.4', 'ONLINE', 'https://kdm-hub.starlight.com/api/v1/pos'),
            (102, 'Auditorium 1 TMS Server', '192.168.4.20', 'Barco TMS 3.0', 'ONLINE', 'http://tms-aud1.aurorapictures.local/kdm'),
            (103, 'Projection Master Booth', '172.16.50.88', 'Dolby CP950 Ingest', 'STANDBY', 'http://dolby-node01.zenith.internal/sync')");
    }

    // 5. Secret Security Vault (CTF Target & Real-World Exfiltration Flag)
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS mx_vault (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_name VARCHAR(100) NOT NULL,
        secret_value VARCHAR(255) NOT NULL,
        classification VARCHAR(50) NOT NULL
    )");

    $chk_vault = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM mx_vault");
    if ($chk_vault && ($row = mysqli_fetch_assoc($chk_vault)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO mx_vault (secret_name, secret_value, classification) VALUES
            ('FLAG_SQLI_EXPLOIT', 'FLAG{m0v1eXch4ng3_5ql1_m45t3r_2026}', 'TOP SECRET'),
            ('MASTER_KDM_PRIVATE_KEY', 'RSA_PRIV_KDM_4096_d892ba990c88efb490', 'RESTRICTED'),
            ('DCP_AWS_S3_KEY', 'AKIA_MX_FILM_INGEST_PROD_99814', 'CONFIDENTIAL')");
    }
}
setup_moviexchange_schema($conn);

// ── Routing & Actions ───────────────────────────────────────────────────────
$view = $_GET['view'] ?? 'login';
$msg_error = '';
$msg_success = '';
$msg_info = '';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php?view=login");
    exit();
}

// ── Feature 1: Login Authentication Bypass ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $msg_error = "Please enter both email and password.";
    } else {
        $sql = "SELECT * FROM mx_users WHERE email = '$email'";
        $result = @mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password']) || strpos($email, "'") !== false) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'partner';
                $_SESSION['user_company'] = $user['company'] ?? 'Partner Cinema';
                header("Location: index.php?view=portal");
                exit();
            } else {
                $msg_error = "Invalid password for user account.";
            }
        } else {
            $msg_error = "No user found with the provided email address.";
        }
    }
}

// ── Feature 2: Forgot Password (Blind / Time-based SQLi) ────────────────────
$forgot_submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'forgot_password') {
    $email = $_POST['email'] ?? '';
    $forgot_submitted = true;

    if (empty($email)) {
        $msg_error = "Please provide your registered account email.";
    } else {
        $sql = "SELECT id, email, full_name FROM mx_users WHERE email = '$email'";
        $result = @mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $msg_success = "Password reset instructions and verification link have been dispatched to your email address.";
        } else {
            $msg_info = "If an account matching that email address exists in our database, a recovery link has been sent.";
        }
    }
}

// ── Feature 3: Account Application Registration ─────────────────────────────
$app_created_ref = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'account_apply') {
    $fname = trim($_POST['first_name'] ?? '');
    $lname = trim($_POST['last_name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $comp_type = $_POST['company_type'] ?? '';
    $country = $_POST['country'] ?? '';
    $cinema_site = trim($_POST['cinema_site'] ?? '');
    $pos_op = $_POST['pos_operator'] ?? '';
    $phone = trim($_POST['contact_phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $details = trim($_POST['additional_details'] ?? '');

    if (empty($fname) || empty($lname) || empty($company) || empty($email)) {
        $msg_error = "Please fill in all required application fields.";
    } else {
        $new_ref = "APP-" . date('Y') . "-" . rand(1000, 9999);
        $clean_details = mysqli_real_escape_string($conn, $details);
        
        $sql = "INSERT INTO mx_applications (ref_no, first_name, last_name, company, company_type, country, cinema_site, pos_operator, phone, email, details) VALUES 
            ('$new_ref', '$fname', '$lname', '$company', '$comp_type', '$country', '$cinema_site', '$pos_op', '$phone', '$email', '$clean_details')";
        
        $res = @mysqli_query($conn, $sql);
        if ($res) {
            $app_created_ref = $new_ref;
            $msg_success = "Application successfully registered! Your Reference Number is <strong>$new_ref</strong>. You can track your status in the Status Tracker.";
        } else {
            $msg_error = "Application submission failed: " . mysqli_error($conn);
        }
    }
}

// ── Feature 4: Application Status Tracker (UNION & Error-based SQLi) ─────────
$tracking_results = null;
if (isset($_GET['ref_no'])) {
    $search_ref = $_GET['ref_no'];
    $sql = "SELECT ref_no, first_name, last_name, company, company_type, country, cinema_site, pos_operator, phone, email, details, status, internal_notes, submitted_at FROM mx_applications WHERE ref_no = '$search_ref' OR email = '$search_ref'";
    $tracking_results = @mysqli_query($conn, $sql);
}

// ── Feature 5: Film Distribution Catalog (ORDER BY SQLi) ────────────────────
$catalog_results = null;
if ($view === 'catalog' || $view === 'portal') {
    $order_by = $_GET['order_by'] ?? 'release_date';
    $dir = $_GET['dir'] ?? 'DESC';
    $genre_filter = $_GET['search'] ?? '';

    $where_clause = "";
    if (!empty($genre_filter)) {
        $where_clause = "WHERE title LIKE '%$genre_filter%' OR distributor LIKE '%$genre_filter%'";
    }
    $sql = "SELECT * FROM mx_films $where_clause ORDER BY $order_by $dir";
    $catalog_results = @mysqli_query($conn, $sql);
}

// ── Feature 6: Cinema POS Terminal Registry (Integer / Numeric SQLi) ────────
$terminal_results = null;
if ($view === 'pos') {
    $site_param = $_GET['site_id'] ?? '101';
    $sql = "SELECT * FROM mx_terminals WHERE site_id = $site_param";
    $terminal_results = @mysqli_query($conn, $sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>movieXchange — Film Distribution & Cinema POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --mx-brand-pink: #ec407a;
            --mx-brand-cyan: #26c6da;
            --mx-primary-blue: #0288d1;
            --mx-primary-hover: #0277bd;
            --mx-bg-dark: #0f1626;
            --mx-card-bg: #ffffff;
            --mx-text-muted: #6b7280;
            --mx-border: #e5e7eb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: #0b111e;
            color: #1f2937;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* ── Realistic Bokeh Night City Backdrop ────────────────────────────── */
        .bokeh-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: 
                radial-gradient(circle at 20% 35%, rgba(236, 64, 122, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 80% 40%, rgba(38, 198, 218, 0.18) 0%, transparent 45%),
                radial-gradient(circle at 50% 85%, rgba(2, 136, 209, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 10% 75%, rgba(255, 179, 0, 0.12) 0%, transparent 35%),
                radial-gradient(circle at 90% 80%, rgba(255, 112, 67, 0.15) 0%, transparent 35%),
                linear-gradient(180deg, #090e18 0%, #121c2e 50%, #080d16 100%);
            overflow: hidden;
        }

        /* Bokeh light circles */
        .bokeh-circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.35;
            pointer-events: none;
            animation: floatSlow 18s ease-in-out infinite alternate;
        }
        .bokeh-1 { width: 380px; height: 380px; background: #ec407a; top: 10%; left: 8%; }
        .bokeh-2 { width: 440px; height: 440px; background: #00bcd4; top: 15%; right: 12%; animation-delay: -5s; }
        .bokeh-3 { width: 500px; height: 500px; background: #ffb74d; bottom: 5%; left: 15%; animation-delay: -10s; }
        .bokeh-4 { width: 360px; height: 360px; background: #ab47bc; bottom: 10%; right: 20%; animation-delay: -7s; }

        @keyframes floatSlow {
            0% { transform: translateY(0px) scale(1); }
            100% { transform: translateY(-40px) scale(1.08); }
        }

        /* ── Top Bar & Navigation ───────────────────────────────────────────── */
        .mx-top-bar {
            background: rgba(11, 17, 30, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 10px 24px;
            font-size: 0.85rem;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .mx-top-bar a {
            color: #cbd5e1;
            text-decoration: none;
            margin-left: 18px;
            transition: color 0.2s;
        }
        .mx-top-bar a:hover {
            color: #ffffff;
        }

        /* ── Brand Logo Header ──────────────────────────────────────────────── */
        .brand-header {
            text-align: center;
            padding: 40px 0 24px;
        }
        .brand-logo-svg {
            width: 72px;
            height: 72px;
            margin-bottom: 8px;
            filter: drop-shadow(0 6px 16px rgba(236, 64, 122, 0.25));
        }
        .brand-title {
            color: #ffffff;
            font-weight: 600;
            font-size: 2rem;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .brand-title span {
            font-weight: 400;
        }

        /* ── Main Form Cards ────────────────────────────────────────────────── */
        .mx-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
            padding: 36px 36px 28px;
            width: 100%;
            margin-bottom: 30px;
        }
        .mx-card-login {
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }
        .mx-card-apply {
            max-width: 620px;
            margin-left: auto;
            margin-right: auto;
        }
        .mx-card-portal {
            max-width: 1140px;
            margin-left: auto;
            margin-right: auto;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 6px;
        }
        .form-control, .form-select {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px 14px;
            font-size: 0.92rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0288d1;
            box-shadow: 0 0 0 3px rgba(2, 136, 209, 0.15);
        }

        .btn-mx-primary {
            background: #0288d1;
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 10px 24px;
            border-radius: 6px;
            border: none;
            transition: all 0.2s;
        }
        .btn-mx-primary:hover {
            background: #0277bd;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 136, 209, 0.35);
        }

        .link-mx {
            color: #0288d1;
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.15s;
        }
        .link-mx:hover {
            color: #01579b;
            text-decoration: underline;
        }

        .terms-note {
            font-size: 0.75rem;
            color: #6b7280;
            text-align: left;
            margin-top: 20px;
        }
        .terms-note a {
            color: #0288d1;
            text-decoration: none;
        }

        /* ── hCaptcha Mock Box ──────────────────────────────────────────────── */
        .captcha-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .captcha-left {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
            color: #374151;
            font-weight: 500;
        }
        .captcha-left input[type="checkbox"] {
            width: 22px;
            height: 22px;
            accent-color: #0288d1;
            cursor: pointer;
        }
        .captcha-right {
            text-align: center;
            font-size: 0.65rem;
            color: #9ca3af;
        }
        .captcha-logo {
            width: 28px;
            height: 28px;
            margin-bottom: 2px;
        }

        /* ── Bottom Global Footer ───────────────────────────────────────────── */
        .mx-footer {
            margin-top: auto;
            background: rgba(11, 17, 30, 0.9);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding: 18px 30px;
            color: #94a3b8;
            font-size: 0.82rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .mx-footer a {
            color: #cbd5e1;
            text-decoration: none;
            margin: 0 12px;
            transition: color 0.2s;
        }
        .mx-footer a:hover {
            color: #38bdf8;
        }
    </style>
</head>
<body>

    <!-- Bokeh Lights Backdrop -->
    <div class="bokeh-background">
        <div class="bokeh-circle bokeh-1"></div>
        <div class="bokeh-circle bokeh-2"></div>
        <div class="bokeh-circle bokeh-3"></div>
        <div class="bokeh-circle bokeh-4"></div>
    </div>

    <!-- Top Navigation Bar -->
    <div class="mx-top-bar">
        <div>
            <span class="badge bg-secondary me-2">ENTERPRISE FILM PORTAL</span>
            <span class="text-white-50 d-none d-md-inline">film.movieexchange.com</span>
        </div>
        <div>
            <a href="?view=login" class="<?php echo ($view === 'login') ? 'text-info fw-bold' : ''; ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
            <a href="?view=forgot" class="<?php echo ($view === 'forgot') ? 'text-info fw-bold' : ''; ?>"><i class="bi bi-key me-1"></i>Forgot Password</a>
            <a href="?view=apply" class="<?php echo ($view === 'apply') ? 'text-info fw-bold' : ''; ?>"><i class="bi bi-file-earmark-person me-1"></i>Account Application</a>
            <a href="?view=status" class="<?php echo ($view === 'status') ? 'text-info fw-bold' : ''; ?>"><i class="bi bi-search me-1"></i>Track Application</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="?view=portal" class="text-warning fw-bold"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                <a href="?action=logout" class="text-danger"><i class="bi bi-power me-1"></i>Logout</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Central Logo Header -->
    <div class="brand-header">
        <!-- SVG movieXchange Logo -->
        <svg class="brand-logo-svg" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="mxGradPink" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#ff4081" />
                    <stop offset="100%" stop-color="#d81b60" />
                </linearGradient>
                <linearGradient id="mxGradCyan" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#00e5ff" />
                    <stop offset="100%" stop-color="#0091ea" />
                </linearGradient>
            </defs>
            <!-- Overlapping Pink & Cyan Diamonds / Movie Ribbons -->
            <path d="M 28 20 Q 32 15 38 20 L 75 60 Q 80 65 75 70 L 62 82 Q 58 86 52 82 L 15 42 Q 10 38 15 32 Z" fill="url(#mxGradPink)" opacity="0.92" />
            <path d="M 72 20 Q 68 15 62 20 L 25 60 Q 20 65 25 70 L 38 82 Q 42 86 48 82 L 85 42 Q 90 38 85 32 Z" fill="url(#mxGradCyan)" opacity="0.88" style="mix-blend-mode: screen;" />
            <polygon points="50,37 63,50 50,63 37,50" fill="#ffffff" opacity="0.95" />
        </svg>
        <h1 class="brand-title">movie<span>Xchange</span></h1>
    </div>

    <!-- Main Content Views Container -->
    <div class="container mb-5">

        <!-- ================================================================= -->
        <!-- VIEW 1: LOGIN (Screenshot 1 Match)                                -->
        <!-- ================================================================= -->
        <?php if ($view === 'login'): ?>
            <div class="mx-card mx-card-login">
                <?php if ($msg_error): ?>
                    <div class="alert alert-danger py-2 mb-3" style="font-size:0.88rem;"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $msg_error; ?></div>
                <?php endif; ?>
                <?php if ($msg_success): ?>
                    <div class="alert alert-success py-2 mb-3" style="font-size:0.88rem;"><i class="bi bi-check-circle-fill me-2"></i><?php echo $msg_success; ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php?view=login">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="mb-3">
                        <label class="form-label" for="loginEmail">Email</label>
                        <input type="text" id="loginEmail" name="email" class="form-control" placeholder="" autocomplete="email" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="loginPassword">Password</label>
                        <div class="input-group">
                            <input type="password" id="loginPassword" name="password" class="form-control" placeholder="" autocomplete="current-password" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('loginPassword', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-mx-primary">Login</button>
                    </div>

                    <div class="mb-2">
                        <a href="?view=forgot" class="link-mx">Forgot password?</a>
                    </div>

                    <div class="mb-3" style="font-size: 0.875rem;">
                        No account? <a href="?view=apply" class="link-mx">Apply here</a>
                    </div>

                    <div class="terms-note">
                        By accessing movieXchange Film you agree to our <a href="#">Terms of Use</a>
                    </div>
                </form>
            </div>

        <!-- ================================================================= -->
        <!-- VIEW 2: FORGOT PASSWORD (Screenshot 2 Match)                      -->
        <!-- ================================================================= -->
        <?php elseif ($view === 'forgot'): ?>
            <div class="mx-card mx-card-login">
                <h5 class="fw-bold mb-3" style="font-size: 1.1rem; color: #1f2937;">Forgot password?</h5>

                <?php if ($msg_error): ?>
                    <div class="alert alert-danger py-2 mb-3" style="font-size:0.88rem;"><?php echo $msg_error; ?></div>
                <?php endif; ?>
                <?php if ($msg_success): ?>
                    <div class="alert alert-success py-2 mb-3" style="font-size:0.88rem;"><?php echo $msg_success; ?></div>
                <?php endif; ?>
                <?php if ($msg_info): ?>
                    <div class="alert alert-info py-2 mb-3" style="font-size:0.88rem;"><?php echo $msg_info; ?></div>
                <?php endif; ?>

                <form method="POST" action="index.php?view=forgot">
                    <input type="hidden" name="action" value="forgot_password">

                    <div class="mb-4">
                        <label class="form-label" for="forgotEmail">Email</label>
                        <input type="text" id="forgotEmail" name="email" class="form-control" placeholder="" required autofocus>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="?view=login" class="link-mx">Return to login</a>
                        <button type="submit" class="btn btn-mx-primary px-4">Send</button>
                    </div>
                </form>
            </div>

        <!-- ================================================================= -->
        <!-- VIEW 3: ACCOUNT APPLICATION (Screenshots 3 & 4 Match)             -->
        <!-- ================================================================= -->
        <?php elseif ($view === 'apply'): ?>
            <div class="mx-card mx-card-apply">
                <p class="text-muted mb-4" style="font-size: 0.88rem; line-height: 1.45;">
                    Apply for a movieXchange account.<br>
                    Applications can take up to 3 working days to be verified and processed.
                </p>

                <?php if ($msg_error): ?>
                    <div class="alert alert-danger py-2 mb-3" style="font-size:0.88rem;"><?php echo $msg_error; ?></div>
                <?php endif; ?>
                <?php if ($msg_success): ?>
                    <div class="alert alert-success py-3 mb-4" style="font-size:0.9rem;">
                        <?php echo $msg_success; ?>
                        <div class="mt-2">
                            <a href="?view=status&ref_no=<?php echo urlencode($app_created_ref); ?>" class="btn btn-sm btn-outline-success">Check Application Status Now &rarr;</a>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?view=apply">
                    <input type="hidden" name="action" value="account_apply">

                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" name="company" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Company Type</label>
                        <select name="company_type" class="form-select">
                            <option value="">Select company type...</option>
                            <option value="Cinema Chain">Cinema Chain</option>
                            <option value="Exhibitor">Exhibitor</option>
                            <option value="Distributor">Distributor</option>
                            <option value="Production House">Production House</option>
                            <option value="Independent Theatre">Independent Theatre</option>
                            <option value="Film Festival">Film Festival</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Country</label>
                        <select name="country" class="form-select">
                            <option value="">Select country...</option>
                            <option value="United States">United States</option>
                            <option value="United Kingdom">United Kingdom</option>
                            <option value="Australia">Australia</option>
                            <option value="Canada">Canada</option>
                            <option value="New Zealand">New Zealand</option>
                            <option value="Germany">Germany</option>
                            <option value="France">France</option>
                            <option value="Singapore">Singapore</option>
                            <option value="India">India</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Cinema Site</label>
                        <input type="text" name="cinema_site" class="form-control" placeholder="e.g. Regent Theatre Melbourne">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">POS Operator</label>
                        <select name="pos_operator" class="form-select">
                            <option value="">Select POS operator...</option>
                            <option value="Vista Cinema">Vista Cinema</option>
                            <option value="Veezi">Veezi</option>
                            <option value="RTS POS">RTS POS</option>
                            <option value="Compeso">Compeso</option>
                            <option value="Agile Ticketing">Agile Ticketing</option>
                            <option value="In-House POS">In-House POS</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="contact_phone" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Additional Details</label>
                        <textarea name="additional_details" class="form-control" rows="3" placeholder="Tell us more about your needs or what you are trying to achieve. This will help us provide the best solution."></textarea>
                    </div>

                    <!-- hCaptcha Verification Box Mock -->
                    <div class="captcha-box">
                        <div class="captcha-left">
                            <input type="checkbox" id="captchaCheck" checked required>
                            <label for="captchaCheck" style="margin:0;cursor:pointer;">I am human</label>
                        </div>
                        <div class="captcha-right">
                            <svg class="captcha-logo" viewBox="0 0 48 48" fill="#00bcd4">
                                <circle cx="24" cy="24" r="20" fill="#e0f7fa" />
                                <path d="M24 10a14 14 0 1 0 14 14A14 14 0 0 0 24 10zm-2 20l-5-5 1.4-1.4 3.6 3.6 7.6-7.6L31 21z" fill="#00acc1"/>
                            </svg>
                            <div>hCaptcha</div>
                            <div style="font-size:0.55rem;"><a href="#" class="text-muted">Privacy</a> • <a href="#" class="text-muted">Terms</a></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="?view=login" class="link-mx">Already have an account?</a>
                        <button type="submit" class="btn btn-mx-primary px-4">Apply</button>
                    </div>

                    <div class="terms-note">
                        By accessing movieXchange Film you agree to our <a href="#">Terms of Use</a>
                    </div>
                </form>
            </div>

        <!-- ================================================================= -->
        <!-- VIEW 4: APPLICATION STATUS TRACKER (UNION SQLi Target)            -->
        <!-- ================================================================= -->
        <?php elseif ($view === 'status'): ?>
            <div class="mx-card mx-card-apply">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-search text-primary fs-4"></i>
                    <h5 class="fw-bold m-0" style="font-size: 1.15rem; color: #1f2937;">Track Application Status</h5>
                </div>
                <p class="text-muted mb-4" style="font-size: 0.88rem;">
                    Enter your <strong>Application Reference Number</strong> (e.g. <code>APP-2026-8821</code>) or your registered business email to view application verification status and internal auditor notes.
                </p>

                <form method="GET" action="index.php" class="mb-4">
                    <input type="hidden" name="view" value="status">
                    <div class="input-group">
                        <input type="text" name="ref_no" class="form-control" placeholder="e.g. APP-2026-8821 or user@example.com" value="<?php echo htmlspecialchars($_GET['ref_no'] ?? ''); ?>" required>
                        <button type="submit" class="btn btn-mx-primary"><i class="bi bi-search me-1"></i> Track</button>
                    </div>
                </form>

                <?php if (isset($_GET['ref_no'])): ?>
                    <div class="mt-4">
                        <h6 class="fw-bold text-secondary mb-3">Search Query Results</h6>
                        <?php if ($tracking_results && mysqli_num_rows($tracking_results) > 0): ?>
                            <?php while ($app = mysqli_fetch_assoc($tracking_results)): ?>
                                <div class="border rounded p-3 mb-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="badge bg-dark me-2"><?php echo htmlspecialchars($app['ref_no'] ?? 'N/A'); ?></span>
                                            <strong class="text-dark"><?php echo htmlspecialchars(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? '')); ?></strong>
                                        </div>
                                        <span class="badge <?php echo (($app['status'] ?? '') === 'Approved') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                            <?php echo htmlspecialchars($app['status'] ?? 'Under Review'); ?>
                                        </span>
                                    </div>
                                    <div class="row g-2 text-muted" style="font-size: 0.85rem;">
                                        <div class="col-6"><strong>Company:</strong> <?php echo htmlspecialchars($app['company'] ?? 'N/A'); ?></div>
                                        <div class="col-6"><strong>Cinema Site:</strong> <?php echo htmlspecialchars($app['cinema_site'] ?? 'N/A'); ?></div>
                                        <div class="col-6"><strong>POS:</strong> <?php echo htmlspecialchars($app['pos_operator'] ?? 'N/A'); ?></div>
                                        <div class="col-6"><strong>Email:</strong> <?php echo htmlspecialchars($app['email'] ?? 'N/A'); ?></div>
                                        <div class="col-12 mt-2 pt-2 border-top">
                                            <strong>Auditor Notes:</strong>
                                            <div class="p-2 mt-1 rounded bg-white border text-dark font-monospace" style="font-size:0.8rem;">
                                                <?php echo htmlspecialchars($app['internal_notes'] ?? 'No internal notes logged.'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-warning" style="font-size:0.88rem;">
                                <i class="bi bi-info-circle me-2"></i>No application records found matching that reference number.
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        <!-- ================================================================= -->
        <!-- VIEW 5: AUTHENTICATED PORTAL & MULTI-FEATURE DASHBOARD            -->
        <!-- ================================================================= -->
        <?php else: ?>
            <div class="mx-card mx-card-portal">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom flex-wrap gap-2">
                    <div>
                        <h4 class="fw-bold mb-1" style="color: #0f172a;">
                            <i class="bi bi-film text-info me-2"></i>movieXchange Film Operations
                        </h4>
                        <div class="text-muted" style="font-size:0.85rem;">
                            Authenticated as <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest Operator'); ?></strong> 
                            (<code><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'admin@moviexchange.com'); ?></code>) • 
                            Role: <span class="badge bg-primary"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Partner'); ?></span>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?view=portal" class="btn btn-sm <?php echo ($view === 'portal' || $view === 'catalog') ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                            <i class="bi bi-camera-reels me-1"></i>DCP Catalog
                        </a>
                        <a href="?view=pos" class="btn btn-sm <?php echo ($view === 'pos') ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                            <i class="bi bi-hdd-network me-1"></i>POS Terminals
                        </a>
                        <a href="?action=logout" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </div>
                </div>

                <!-- SUB-VIEW A: DCP Catalog (ORDER BY SQLi) -->
                <?php if ($view === 'portal' || $view === 'catalog'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h6 class="fw-bold text-dark m-0"><i class="bi bi-grid-3x3-gap me-2"></i>DCP Feature Film Releases & KDM Status</h6>
                        
                        <!-- Sort Filter (Vulnerable to ORDER BY SQLi) -->
                        <form method="GET" action="index.php" class="d-flex gap-2 align-items-center">
                            <input type="hidden" name="view" value="catalog">
                            <label class="text-muted small m-0">Sort By:</label>
                            <select name="order_by" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <option value="release_date" <?php echo (($_GET['order_by'] ?? '') === 'release_date') ? 'selected' : ''; ?>>Release Date</option>
                                <option value="title" <?php echo (($_GET['order_by'] ?? '') === 'title') ? 'selected' : ''; ?>>Title</option>
                                <option value="duration_mins" <?php echo (($_GET['order_by'] ?? '') === 'duration_mins') ? 'selected' : ''; ?>>Duration</option>
                                <option value="distributor" <?php echo (($_GET['order_by'] ?? '') === 'distributor') ? 'selected' : ''; ?>>Distributor</option>
                            </select>
                            <select name="dir" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <option value="DESC" <?php echo (($_GET['dir'] ?? '') === 'DESC') ? 'selected' : ''; ?>>DESC</option>
                                <option value="ASC" <?php echo (($_GET['dir'] ?? '') === 'ASC') ? 'selected' : ''; ?>>ASC</option>
                            </select>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border" style="font-size:0.88rem;">
                            <thead class="table-dark">
                                <tr>
                                    <th>Film Title</th>
                                    <th>Distributor</th>
                                    <th>DCP Package Format</th>
                                    <th>Audio Channels</th>
                                    <th>Aspect Ratio</th>
                                    <th>Runtime</th>
                                    <th>Release Date</th>
                                    <th>KDM Key</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($catalog_results && mysqli_num_rows($catalog_results) > 0): ?>
                                    <?php while ($film = mysqli_fetch_assoc($catalog_results)): ?>
                                        <tr>
                                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($film['title']); ?></td>
                                            <td><?php echo htmlspecialchars($film['distributor']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($film['dcp_format']); ?></span></td>
                                            <td><?php echo htmlspecialchars($film['audio_channels']); ?></td>
                                            <td><?php echo htmlspecialchars($film['aspect_ratio']); ?></td>
                                            <td><?php echo htmlspecialchars($film['duration_mins']); ?> mins</td>
                                            <td><?php echo htmlspecialchars($film['release_date']); ?></td>
                                            <td>
                                                <span class="badge <?php echo ($film['kdm_status'] === 'Active KDM') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                    <i class="bi bi-shield-lock-fill me-1"></i><?php echo htmlspecialchars($film['kdm_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center py-4 text-muted">No films returned.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                <!-- SUB-VIEW B: Cinema POS Terminals (Integer SQLi) -->
                <?php elseif ($view === 'pos'): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                        <h6 class="fw-bold text-dark m-0"><i class="bi bi-hdd-rack me-2"></i>Cinema POS & Ingest Terminal Management</h6>
                        <div class="btn-group btn-group-sm">
                            <a href="?view=pos&site_id=101" class="btn <?php echo (($_GET['site_id'] ?? '101') == '101') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Site 101 (Starlight)</a>
                            <a href="?view=pos&site_id=102" class="btn <?php echo (($_GET['site_id'] ?? '') == '102') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Site 102 (Aurora)</a>
                            <a href="?view=pos&site_id=103" class="btn <?php echo (($_GET['site_id'] ?? '') == '103') ? 'btn-primary' : 'btn-outline-secondary'; ?>">Site 103 (Zenith)</a>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle border" style="font-size:0.88rem;">
                            <thead class="table-dark">
                                <tr>
                                    <th>Terminal ID</th>
                                    <th>Site ID</th>
                                    <th>Terminal Name</th>
                                    <th>Internal IP</th>
                                    <th>POS Software</th>
                                    <th>Sync Endpoint</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($terminal_results && mysqli_num_rows($terminal_results) > 0): ?>
                                    <?php while ($t = mysqli_fetch_assoc($terminal_results)): ?>
                                        <tr>
                                            <td><code>#<?php echo htmlspecialchars($t['terminal_id'] ?? 'N/A'); ?></code></td>
                                            <td><span class="badge bg-secondary">Site <?php echo htmlspecialchars($t['site_id'] ?? 'N/A'); ?></span></td>
                                            <td class="fw-bold"><?php echo htmlspecialchars($t['terminal_name'] ?? 'N/A'); ?></td>
                                            <td><code><?php echo htmlspecialchars($t['ip_address'] ?? 'N/A'); ?></code></td>
                                            <td><?php echo htmlspecialchars($t['pos_software'] ?? 'N/A'); ?></td>
                                            <td><code class="text-primary"><?php echo htmlspecialchars($t['kdm_endpoint'] ?? 'N/A'); ?></code></td>
                                            <td><span class="badge bg-success"><?php echo htmlspecialchars($t['status'] ?? 'ONLINE'); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center py-4 text-muted">No terminals found for this query.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Bottom Footer (Matching Screenshot 1 & 3) -->
    <footer class="mx-footer">
        <div>
            All Rights Reserved 2026
        </div>
        <div class="d-flex align-items-center flex-wrap">
            <a href="#">Terms of Use</a>
            <a href="#">Contact Us</a>
            <a href="#">Release Notes <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem;"></i></a>
            <a href="#">Integration Documentation <i class="bi bi-box-arrow-up-right" style="font-size:0.7rem;"></i></a>
        </div>
        <div>
            <svg viewBox="0 0 100 100" fill="none" style="width: 22px; height: 22px; vertical-align: middle;">
                <path d="M 28 20 Q 32 15 38 20 L 75 60 Q 80 65 75 70 L 62 82 Q 58 86 52 82 L 15 42 Q 10 38 15 32 Z" fill="#ff4081" />
                <path d="M 72 20 Q 68 15 62 20 L 25 60 Q 20 65 25 70 L 38 82 Q 42 86 48 82 L 85 42 Q 90 38 85 32 Z" fill="#00e5ff" opacity="0.85" style="mix-blend-mode: screen;" />
            </svg>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
