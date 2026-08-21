<?php
$returnTo = '';
if (isset($_GET['returnTo'])) {
    $returnTo = $_GET['returnTo'];
}

$safe_return = htmlspecialchars($returnTo, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopify Help Center — Confirm Account Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --shopify-green:      #008060;
            --shopify-green-dark: #004c3f;
            --shopify-green-lite: #e3f1ec;
            --shopify-border:     #c9cccf;
            --shopify-bg:         #f6f6f7;
            --shopify-text:       #202223;
            --shopify-muted:      #6d7175;
            --shopify-red:        #d72c0d;
        }

        body {
            background: var(--shopify-bg);
            color: var(--shopify-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }
        .lab-badge-real {
            background: linear-gradient(90deg, #008060, #004c3f);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
        }
        .lab-topbar-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* ── Site header ── */
        .site-header {
            background: #fff;
            border-bottom: 1px solid var(--shopify-border);
        }
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .logo-icon {
            width: 32px;
            height: 32px;
        }
        .logo-text {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--shopify-green-dark);
        }
        .header-nav {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }
        .header-nav a {
            color: var(--shopify-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .header-nav a:hover { color: var(--shopify-text); }
        .header-nav .btn-signin {
            background: var(--shopify-green);
            color: #fff;
            padding: 0.45rem 1rem;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .header-nav .btn-signin:hover { background: var(--shopify-green-dark); }

        /* ── Sub nav ── */
        .sub-nav {
            background: var(--shopify-green-dark);
            padding: 0;
        }
        .sub-nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            gap: 0;
        }
        .sub-nav a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 500;
            padding: 0.65rem 1rem;
            display: block;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .sub-nav a:hover, .sub-nav a.active {
            color: #fff;
            border-bottom-color: #fff;
        }

        /* ── Breadcrumb ── */
        .breadcrumb-wrap {
            background: #fff;
            border-bottom: 1px solid var(--shopify-border);
            padding: 0.6rem 2rem;
        }
        .breadcrumb {
            max-width: 1200px;
            margin: 0 auto;
            font-size: 0.78rem;
            color: var(--shopify-muted);
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .breadcrumb a { color: var(--shopify-green); text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }

        /* ── Page layout ── */
        .page-wrap {
            max-width: 560px;
            margin: 3rem auto;
            padding: 0 1.5rem 4rem;
        }

        /* ── Card ── */
        .card {
            background: #fff;
            border: 1px solid var(--shopify-border);
            border-radius: 8px;
            padding: 2rem 2rem 2.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .card-header {
            margin-bottom: 1.75rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--shopify-border);
        }
        .card-header h1 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--shopify-text);
            margin-bottom: 0.4rem;
        }
        .card-header p {
            font-size: 0.85rem;
            color: var(--shopify-muted);
            line-height: 1.5;
        }

        /* ── Notice banner ── */
        .notice {
            background: #fff8e6;
            border: 1px solid #ffd366;
            border-left: 4px solid #ffc453;
            border-radius: 6px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .notice i { color: #b98900; font-size: 0.9rem; margin-top: 0.05rem; }
        .notice p { font-size: 0.82rem; color: #4a3700; line-height: 1.5; }

        /* ── Form fields ── */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--shopify-text);
            margin-bottom: 0.35rem;
        }
        .form-group input[type="text"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 1px solid var(--shopify-border);
            border-radius: 6px;
            font-size: 0.9rem;
            font-family: inherit;
            color: var(--shopify-text);
            background: #fff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus {
            border-color: var(--shopify-green);
            box-shadow: 0 0 0 3px rgba(0,128,96,0.12);
        }
        .form-group .hint {
            font-size: 0.75rem;
            color: var(--shopify-muted);
            margin-top: 0.3rem;
        }
        .required-mark { color: var(--shopify-red); margin-left: 2px; }

        /* ── Actions ── */
        .form-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.75rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--shopify-border);
        }
        .btn-cancel {
            color: var(--shopify-muted);
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
        }
        .btn-cancel:hover { color: var(--shopify-text); text-decoration: underline; }

        /*
         * VULNERABLE ELEMENT:
         * The $returnTo parameter is echoed raw into this anchor's href.
         * Setting returnTo=javascript:alert(document.cookie) will execute JS on click.
         * No protocol validation is applied — any URI scheme is accepted.
         */
        .btn-continue {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: var(--shopify-green);
            color: #fff;
            padding: 0.65rem 1.4rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            font-family: inherit;
            transition: background 0.2s;
        }
        .btn-continue:hover { background: var(--shopify-green-dark); color: #fff; }

        /* ── Security note ── */
        .security-note {
            margin-top: 1rem;
            font-size: 0.75rem;
            color: var(--shopify-muted);
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }
        .lab-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #1e293b;
        }
        .lab-meta-item { font-size: 0.78rem; color: #64748b; }
        .lab-meta-item strong { color: #94a3b8; }

        /* ── Footer ── */
        footer {
            text-align: center;
            padding: 2rem;
            font-size: 0.78rem;
            color: var(--shopify-muted);
            border-top: 1px solid var(--shopify-border);
            background: #fff;
            margin-top: 3rem;
        }
        footer a { color: var(--shopify-green); text-decoration: none; }
    </style>
</head>
<body>

    <!-- Site header -->
    <header class="site-header">
        <div class="header-inner">
            <a href="#" class="logo">
                <!-- Shopify bag icon (SVG) -->
                <svg class="logo-icon" viewBox="0 0 109.5 124.5" xmlns="http://www.w3.org/2000/svg">
                    <path d="M74.7,14.8c0,0-1.4,0.4-3.7,1.1c-0.4-1.3-1-2.8-1.8-4.4c-2.6-5-6.5-7.7-11.1-7.7c0,0,0,0,0,0
                    c-0.3,0-0.6,0-1,0.1c-0.1-0.2-0.3-0.3-0.4-0.5c-2-2.2-4.6-3.2-7.7-3.1c-6,0.2-12,4.5-16.8,12.2c-3.4,5.4-6,12.2-6.7,17.5
                    c-6.9,2.1-11.7,3.6-11.8,3.7C11,35.5,10.7,35.8,10.6,36c-0.4,5.4-2.2,55-2.2,55l57.3,9.9L97,94.5C97,94.5,74.7,14.8,74.7,14.8z
                    M57.7,20.1c-4,1.2-8.4,2.6-12.7,3.9c1.2-4.7,3.6-9.4,6.4-12.5c1.1-1.1,2.6-2.4,4.3-3.1C57.4,11.8,57.8,16.8,57.7,20.1z
                    M49.9,4.2c1.4,0,2.6,0.3,3.6,0.9c-1.6,0.8-3.2,2.1-4.7,3.7c-3.8,4.1-6.7,10.5-7.9,16.6c-3.6,1.1-7.2,2.2-10.5,3.2
                    C32.4,18,40.6,4.5,49.9,4.2z M44.6,60.7c0.4,6.4,17.3,7.8,18.3,22.8c0.7,11.8-6.3,19.9-16.4,20.5c-12.2,0.8-18.9-6.4-18.9-6.4
                    l2.6-11c0,0,6.7,5.1,12.1,4.7c3.5-0.2,4.8-3.1,4.7-5.1c-0.5-8.4-14.3-7.9-15.2-21.5c-0.8-11.5,6.8-23.1,23.5-24.2
                    c6.4-0.4,9.7,1.2,9.7,1.2l-3.8,14.3c0,0-4.2-1.9-9.2-1.6C46.2,54.8,44.5,57.7,44.6,60.7z M62.4,19.3c0-3-0.4-7.3-1.8-10.9
                    c4.6,0.9,6.8,6,7.8,9.1C66.4,18.1,64.4,18.7,62.4,19.3z" fill="#008060"/>
                </svg>
                <span class="logo-text">Shopify</span>
            </a>
            <nav class="header-nav">
                <a href="#">Help Center</a>
                <a href="#">Community</a>
                <a href="#">Changelog</a>
                <a href="#" class="btn-signin">Sign In</a>
            </nav>
        </div>
    </header>

    <!-- Sub nav -->
    <nav class="sub-nav">
        <div class="sub-nav-inner">
            <a href="?returnTo=https://accounts.shopify.com">Getting Started</a>
            <a href="?returnTo=https://accounts.shopify.com">Orders</a>
            <a href="?returnTo=https://accounts.shopify.com">Products</a>
            <a href="?returnTo=https://accounts.shopify.com">Payments</a>
            <a href="?returnTo=https://accounts.shopify.com" class="active">Account</a>
            <a href="?returnTo=https://accounts.shopify.com">Support</a>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <div class="breadcrumb-wrap">
        <div class="breadcrumb">
            <a href="#">Help Center</a>
            <i class="bi bi-chevron-right" style="font-size:0.65rem;"></i>
            <a href="#">Account</a>
            <i class="bi bi-chevron-right" style="font-size:0.65rem;"></i>
            <span>Confirm Account Details</span>
        </div>
    </div>

    <!-- Page content -->
    <div class="page-wrap">
        <div class="card">
            <div class="card-header">
                <h1>Confirm your account details</h1>
                <p>To continue accessing Shopify Help Center support, please verify and complete your account information below.</p>
            </div>

            <div class="notice">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <p>Your account is missing required information. Please fill in the fields marked with <strong>*</strong> before continuing.</p>
            </div>

            <form onsubmit="alert('Account details saved (demo).'); return false;">
            <input type="hidden" name="returnTo" value="<?php echo $safe_return; ?>">

            <div class="form-row">
                <div class="form-group">
                    <label>First Name <span class="required-mark">*</span></label>
                    <input type="text" placeholder="First name" value="">
                    <p class="hint">As it appears on your account</p>
                </div>
                <div class="form-group">
                    <label>Last Name <span class="required-mark">*</span></label>
                    <input type="text" placeholder="Last name" value="">
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" value="s*****s@example.com" disabled style="background:#f6f6f7;color:#6d7175;cursor:not-allowed;">
                <p class="hint">Email address cannot be changed here</p>
            </div>

            <div class="form-group">
                <label>Phone (optional)</label>
                <input type="text" placeholder="+1 (555) 000-0000" value="">
            </div>

            <div class="form-actions">
                <!--
                    VULNERABLE LINE:
                    $returnTo is echoed raw into the Cancel link's href.
                    Student discovers this by clicking nav tabs above.
                -->
                <a href="<?php echo $returnTo; ?>" class="btn-cancel">
                    Cancel &amp; return
                </a>

                <button type="submit" class="btn-continue" style="border:none;cursor:pointer;">
                    Save &amp; Continue <i class="bi bi-arrow-right"></i>
                </button>
            </div>
            </form>
        </div>

        <div class="security-note">
            <i class="bi bi-shield-lock-fill" style="color:var(--shopify-green);"></i>
            <span>Your information is protected by 256-bit SSL encryption</span>
        </div>
    </div>

    <footer>
        <p>&copy; 2023 Shopify Inc. &mdash; <a href="#">Terms of Service</a> &mdash; <a href="#">Privacy Policy</a></p>
        <p style="margin-top:0.4rem; font-size:0.7rem; opacity:0.5;">This is a simulated lab environment for security training purposes only.</p>
    </footer>

</body>
</html>
