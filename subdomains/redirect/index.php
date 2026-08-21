<?php
// Lab 1: Basic Open Redirect - URL Parameter
// Vulnerability: Direct redirect without validation

$redirect_url = $_GET['url'] ?? '';

if (!empty($redirect_url)) {
    // Vulnerable: No validation of the redirect URL
    header("Location: " . $redirect_url);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LinkDash — URL Shortener & Link Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #ccfbf1;
            --accent: #f59e0b;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border: #e5e7eb;
            --bg-white: #ffffff;
            --bg-soft: #f0fdfa;
            --shadow: 0 4px 24px rgba(13,148,136,0.12);
            --radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: var(--bg-white);
            color: var(--text-dark);
            line-height: 1.6;
        }
        a { text-decoration: none; }

        /* NAV */
        .navbar-custom {
            padding: 16px 0;
            background: var(--bg-white);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar-custom .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
        }
        .logo i { font-size: 24px; }
        .nav-links { display: flex; align-items: center; gap: 28px; }
        .nav-links a {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--primary); }
        .btn-signup {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-signup:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13,148,136,0.3);
        }

        /* HERO */
        .hero {
            text-align: center;
            padding: 80px 24px 60px;
            background: linear-gradient(180deg, var(--bg-soft) 0%, var(--bg-white) 100%);
        }
        .hero h1 {
            font-size: 44px;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }
        .hero h1 span { color: var(--primary); }
        .hero p {
            font-size: 17px;
            color: var(--text-muted);
            max-width: 530px;
            margin: 0 auto 36px;
        }

        .url-box {
            max-width: 640px;
            margin: 0 auto;
            background: var(--bg-white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 6px;
            display: flex;
            align-items: center;
            box-shadow: var(--shadow);
        }
        .url-box input {
            flex: 1;
            border: none;
            padding: 14px 18px;
            font-size: 15px;
            outline: none;
            background: transparent;
            color: var(--text-dark);
        }
        .url-box input::placeholder { color: #9ca3af; }
        .url-box button {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .url-box button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 48px;
            margin-top: 48px;
        }
        .stat-item { text-align: center; }
        .stat-item .num {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
        }
        .stat-item .label {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* FEATURES */
        .features {
            padding: 80px 24px;
            max-width: 1100px;
            margin: 0 auto;
        }
        .features h2 {
            text-align: center;
            font-size: 30px;
            font-weight: 700;
            margin-bottom: 48px;
        }
        .feat-grid {
            display: grid;
            grid-template-columns: repeat(3,1fr);
            gap: 28px;
        }
        @media (max-width: 768px) {
            .feat-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 32px; }
            .hero-stats { gap: 24px; flex-wrap: wrap; }
        }
        .feat-card {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            text-align: center;
            transition: box-shadow 0.3s, transform 0.2s;
            background: var(--bg-white);
        }
        .feat-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-3px);
        }
        .feat-card .icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 22px;
            color: #fff;
        }
        .feat-card h3 { font-size: 17px; font-weight: 600; margin-bottom: 8px; }
        .feat-card p { font-size: 13px; color: var(--text-muted); line-height: 1.5; }

        /* FOOTER */
        .footer {
            text-align: center;
            padding: 24px;
            border-top: 1px solid var(--border);
            font-size: 13px;
            color: var(--text-muted);
            background: var(--bg-white);
        }
    </style>
</head>
<body>

    <nav class="navbar-custom">
        <div class="container">
            <a href="#" class="logo"><i class="fas fa-link"></i>LinkDash</a>
            <div class="nav-links">
                <a href="#">Features</a>
                <a href="#">Pricing</a>
                <a href="#">API</a>
                <a href="#">Login</a>
                <button class="btn-signup">Sign Up Free</button>
            </div>
        </div>
    </nav>

    <section class="hero">
        <h1>Shorten links. <span>Grow smarter.</span></h1>
        <p>LinkDash transforms your long URLs into short, trackable links. Share them anywhere and see who clicks.</p>

        <div class="url-box">
            <input type="text" id="urlInput" placeholder="Paste your long URL here..." value="<?php echo htmlspecialchars($redirect_url ?: ''); ?>">
            <button onclick="window.location.href='?url='+encodeURIComponent(document.getElementById('urlInput').value)"><i class="fas fa-bolt"></i> Shorten</button>
        </div>

        <div class="hero-stats">
            <div class="stat-item"><div class="num">2.4M</div><div class="label">Links Created</div></div>
            <div class="stat-item"><div class="num">847M</div><div class="label">Clicks Tracked</div></div>
            <div class="stat-item"><div class="num">98.6%</div><div class="label">Uptime</div></div>
        </div>
    </section>

    <section class="features">
        <h2>Everything you need to manage links</h2>
        <div class="feat-grid">
            <div class="feat-card">
                <div class="icon" style="background:linear-gradient(135deg,#0d9488,#14b8a6);"><i class="fas fa-chart-line"></i></div>
                <h3>Real-Time Analytics</h3>
                <p>Track clicks, geographic data, device types, and referral sources for every link you share.</p>
            </div>
            <div class="feat-card">
                <div class="icon" style="background:linear-gradient(135deg,#2563eb,#3b82f6);"><i class="fas fa-globe"></i></div>
                <h3>Custom Domains</h3>
                <p>Use your own domain for branded short links that build trust and increase click-through rates.</p>
            </div>
            <div class="feat-card">
                <div class="icon" style="background:linear-gradient(135deg,#7c3aed,#8b5cf6);"><i class="fas fa-qrcode"></i></div>
                <h3>QR Code Generator</h3>
                <p>Generate QR codes for any shortened link — perfect for print, packaging, and offline campaigns.</p>
            </div>
            <div class="feat-card">
                <div class="icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);"><i class="fas fa-shield-alt"></i></div>
                <h3>Link Rotation</h3>
                <p>Set up rotating destination URLs for A/B testing or traffic distribution across multiple pages.</p>
            </div>
            <div class="feat-card">
                <div class="icon" style="background:linear-gradient(135deg,#ef4444,#f87171);"><i class="fas fa-clock"></i></div>
                <h3>Expiration Rules</h3>
                <p>Set automatic expiration dates for time-sensitive campaigns. Links self-disable after the set date.</p>
            </div>
            <div class="feat-card">
                <div class="icon" style="background:linear-gradient(135deg,#0d9488,#14b8a6);"><i class="fas fa-plug"></i></div>
                <h3>API Access</h3>
                <p>Integrate LinkDash into your workflow with our REST API. Bulk create, update, and analyze links.</p>
            </div>
        </div>
    </section>

    <footer class="footer">
        &copy; 2026 LinkDash. All rights reserved. | URL Shortener &amp; Link Management Platform
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
