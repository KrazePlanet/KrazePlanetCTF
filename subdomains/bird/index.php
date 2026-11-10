<?php

/*
 * RIVERVIEW INSTITUTE OF TECHNOLOGY
 * Official Admissions & Student Enrollment Portal
 * Academic Year 2026-2027
 */

$page = $_GET['page'] ?? 'home';
$allowed_pages = ['home', 'programs', 'admissions', 'tuition', 'apply', 'contact'];
if (!in_array($page, $allowed_pages, true)) {
    $page = 'home';
}

$applied = false;
$ref_id = '';
if ($page === 'apply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($name && $email) {
        $applied = true;
        $ref_id = 'RV-' . date('Y') . '-' . rand(1000, 9999);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admissions & Enrollment | Riverview Institute of Technology</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --rv-navy: #0d1e3a;
    --rv-navy-dark: #081326;
    --rv-crimson: #8a1529;
    --rv-crimson-hover: #a11a32;
    --rv-gold: #c59b27;
    --rv-gold-light: #fdf8e6;
    --rv-slate: #475569;
    --rv-light: #f8fafc;
    --rv-border: #e2e8f0;
    --font-serif: 'Libre Baskerville', Georgia, serif;
    --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
}

* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: var(--font-sans);
    background: #ffffff;
    color: #1e293b;
    line-height: 1.6;
}

/* TOP ANNOUNCEMENT BAR */
.top-notice {
    background: var(--rv-navy-dark);
    color: #94a3b8;
    font-size: 12px;
    padding: 8px 24px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.top-notice-inner {
    max-width: 1240px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.top-notice a {
    color: var(--rv-gold);
    text-decoration: none;
    font-weight: 500;
}
.top-notice a:hover { text-decoration: underline; }
.portal-switch a {
    color: #cbd5e1;
    margin-left: 18px;
    font-size: 11px;
    text-decoration: none;
}
.portal-switch a:hover { color: white; }

/* MAIN HEADER */
header {
    background: #ffffff;
    border-bottom: 1px solid var(--rv-border);
    position: sticky;
    top: 0;
    z-index: 100;
}
.header-inner {
    max-width: 1240px;
    margin: auto;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.institution-brand {
    display: flex;
    align-items: center;
    gap: 16px;
    text-decoration: none;
    color: inherit;
}
.crest-box {
    width: 48px;
    height: 48px;
    background: var(--rv-navy);
    color: var(--rv-gold);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--font-serif);
    font-weight: 700;
    font-size: 22px;
    letter-spacing: 1px;
    border: 2px solid var(--rv-gold);
}
.brand-text h1 {
    margin: 0;
    font-family: var(--font-serif);
    font-size: 20px;
    font-weight: 700;
    letter-spacing: 0.5px;
    color: var(--rv-navy);
    line-height: 1.2;
}
.brand-text span {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--rv-crimson);
    font-weight: 600;
    display: block;
    margin-top: 2px;
}

nav {
    display: flex;
    align-items: center;
    gap: 24px;
}
nav a {
    color: #334155;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
    padding-bottom: 4px;
}
nav a:hover, nav a.active {
    color: var(--rv-crimson);
    border-bottom: 2px solid var(--rv-crimson);
}
.btn-apply-nav {
    background: var(--rv-crimson);
    color: white !important;
    padding: 8px 18px;
    border-radius: 4px;
    font-weight: 600 !important;
    border-bottom: none !important;
    transition: background 0.2s;
}
.btn-apply-nav:hover {
    background: var(--rv-crimson-hover);
    color: white !important;
}

/* HERO SECTION */
.hero {
    background: linear-gradient(135deg, rgba(13,30,58,0.95), rgba(8,19,38,0.92)), #0d1e3a;
    color: white;
    padding: 80px 24px;
    position: relative;
    border-bottom: 4px solid var(--rv-gold);
}
.hero-inner {
    max-width: 1240px;
    margin: auto;
}
.hero-tag {
    display: inline-block;
    background: rgba(197, 155, 39, 0.2);
    color: var(--rv-gold);
    border: 1px solid var(--rv-gold);
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 4px 12px;
    border-radius: 3px;
    margin-bottom: 18px;
}
.hero h2 {
    font-family: var(--font-serif);
    font-size: 46px;
    margin: 0 0 18px 0;
    line-height: 1.15;
    max-width: 820px;
}
.hero p {
    font-size: 18px;
    color: #cbd5e1;
    max-width: 680px;
    line-height: 1.6;
    margin: 0 0 28px 0;
}
.hero-buttons {
    display: flex;
    gap: 16px;
}
.btn-hero-primary {
    background: var(--rv-crimson);
    color: white;
    padding: 13px 28px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: background 0.2s;
}
.btn-hero-primary:hover { background: var(--rv-crimson-hover); }
.btn-hero-secondary {
    background: transparent;
    color: white;
    border: 1px solid rgba(255,255,255,0.4);
    padding: 13px 26px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s;
}
.btn-hero-secondary:hover {
    border-color: white;
    background: rgba(255,255,255,0.08);
}

/* CONTAINER */
.container {
    max-width: 1240px;
    margin: 60px auto;
    padding: 0 24px;
}

/* SECTION TITLES */
.section-header {
    text-align: center;
    margin-bottom: 48px;
}
.section-header span {
    color: var(--rv-crimson);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    font-weight: 700;
    display: block;
    margin-bottom: 8px;
}
.section-header h3 {
    font-family: var(--font-serif);
    font-size: 32px;
    color: var(--rv-navy);
    margin: 0;
}

/* STATS BAR */
.stats-bar {
    background: var(--rv-light);
    border: 1px solid var(--rv-border);
    border-radius: 8px;
    padding: 36px 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    text-align: center;
    margin-top: -40px;
    position: relative;
    z-index: 10;
    box-shadow: 0 10px 25px rgba(0,0,0,0.06);
}
.stat-item {
    border-right: 1px solid var(--rv-border);
    padding: 0 16px;
}
.stat-item:last-child { border-right: none; }
.stat-num {
    font-family: var(--font-serif);
    font-size: 36px;
    font-weight: 700;
    color: var(--rv-navy);
    display: block;
}
.stat-label {
    font-size: 13px;
    color: var(--rv-slate);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 500;
    margin-top: 4px;
}

/* CARD GRID */
.grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
}
.info-card {
    background: white;
    border: 1px solid var(--rv-border);
    border-radius: 8px;
    padding: 32px;
    transition: transform 0.2s, box-shadow 0.2s;
}
.info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.06);
}
.card-icon {
    width: 44px;
    height: 44px;
    background: var(--rv-gold-light);
    color: var(--rv-crimson);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    margin-bottom: 18px;
}
.info-card h4 {
    font-family: var(--font-serif);
    font-size: 20px;
    color: var(--rv-navy);
    margin: 0 0 12px 0;
}
.info-card p {
    color: var(--rv-slate);
    font-size: 14px;
    line-height: 1.6;
    margin: 0 0 18px 0;
}
.card-link {
    color: var(--rv-crimson);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.card-link:hover { text-decoration: underline; }

/* CALLOUT BANNER */
.callout-box {
    background: var(--rv-gold-light);
    border-left: 4px solid var(--rv-gold);
    padding: 24px 30px;
    border-radius: 0 6px 6px 0;
    margin: 48px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.callout-text h4 {
    margin: 0 0 6px 0;
    color: var(--rv-navy);
    font-size: 17px;
}
.callout-text p {
    margin: 0;
    color: #78350f;
    font-size: 13px;
}

/* FORM STYLES */
.form-wrapper {
    background: white;
    border: 1px solid var(--rv-border);
    border-radius: 8px;
    padding: 40px;
    max-width: 720px;
    margin: auto;
    box-shadow: 0 8px 20px rgba(0,0,0,0.04);
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 18px;
}
.form-group {
    margin-bottom: 18px;
}
.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 6px;
    color: var(--rv-navy);
}
.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid var(--rv-border);
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
    transition: border-color 0.2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--rv-crimson);
}
.btn-submit-app {
    background: var(--rv-crimson);
    color: white;
    border: none;
    padding: 13px 32px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.2s;
    width: 100%;
}
.btn-submit-app:hover { background: var(--rv-crimson-hover); }

.alert-success {
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    color: #065f46;
    padding: 18px 24px;
    border-radius: 6px;
    margin-bottom: 24px;
    font-size: 14px;
}

/* FOOTER */
footer {
    background: var(--rv-navy-dark);
    color: #94a3b8;
    padding: 60px 24px 30px 24px;
    margin-top: 100px;
    border-top: 4px solid var(--rv-gold);
}
.footer-inner {
    max-width: 1240px;
    margin: auto;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}
.footer-col h5 {
    color: white;
    font-family: var(--font-serif);
    font-size: 16px;
    margin: 0 0 16px 0;
}
.footer-col p {
    font-size: 13px;
    line-height: 1.7;
    margin: 0;
}
.footer-col ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.footer-col ul li {
    margin-bottom: 10px;
}
.footer-col ul li a {
    color: #94a3b8;
    text-decoration: none;
    font-size: 13px;
    transition: color 0.2s;
}
.footer-col ul li a:hover { color: white; }
.footer-bottom {
    max-width: 1240px;
    margin: auto;
    padding-top: 24px;
    border-top: 1px solid rgba(255,255,255,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
}
.footer-bottom a {
    color: #64748b;
    text-decoration: none;
    margin-left: 18px;
}
.footer-bottom a:hover { color: #94a3b8; }
</style>
</head>
<body>

<div class="top-notice">
    <div class="top-notice-inner">
        <div>
            📢 <strong>Fall 2026 Admissions:</strong> Early Action application deadline is <strong>November 30, 2026</strong>. <a href="?page=apply">Apply online →</a>
        </div>
        <div class="portal-switch">
            <a href="?page=contact">Contact Admissions</a>
            <a href="admin/">Staff & Faculty Portal</a>
        </div>
    </div>
</div>

<header>
    <div class="header-inner">
        <a href="?page=home" class="institution-brand">
            <div class="crest-box">R</div>
            <div class="brand-text">
                <h1>Riverview Institute</h1>
                <span>Office of Admissions</span>
            </div>
        </a>

        <nav>
            <a href="?page=home" class="<?= $page === 'home' ? 'active' : '' ?>">Home</a>
            <a href="?page=programs" class="<?= $page === 'programs' ? 'active' : '' ?>">Programs</a>
            <a href="?page=admissions" class="<?= $page === 'admissions' ? 'active' : '' ?>">Admissions</a>
            <a href="?page=tuition" class="<?= $page === 'tuition' ? 'active' : '' ?>">Tuition & Aid</a>
            <a href="?page=contact" class="<?= $page === 'contact' ? 'active' : '' ?>">Contact</a>
            <a href="?page=apply" class="btn-apply-nav">Apply Now</a>
        </nav>
    </div>
</header>

<?php if ($page === 'home'): ?>

<section class="hero">
    <div class="hero-inner">
        <span class="hero-tag">Undergraduate & Graduate Enrollment</span>
        <h2>Shaping the Next Generation of Global Innovators</h2>
        <p>
            At Riverview Institute of Technology, rigorous scholarship meets hands-on discovery.
            Explore over 65 world-class degree programs across computing, engineering, data science, and business.
        </p>
        <div class="hero-buttons">
            <a href="?page=apply" class="btn-hero-primary">Start Application</a>
            <a href="?page=programs" class="btn-hero-secondary">Explore Academic Majors</a>
        </div>
    </div>
</section>

<div class="container" style="margin-top:0;">
    <div class="stats-bar">
        <div class="stat-item">
            <span class="stat-num">#14</span>
            <span class="stat-label">National STEM Ranking</span>
        </div>
        <div class="stat-item">
            <span class="stat-num">9:1</span>
            <span class="stat-label">Student-to-Faculty Ratio</span>
        </div>
        <div class="stat-item">
            <span class="stat-num">94%</span>
            <span class="stat-label">Career Placement Rate</span>
        </div>
        <div class="stat-item">
            <span class="stat-num">$38M</span>
            <span class="stat-label">Annual Research Grants</span>
        </div>
    </div>
</div>

<main class="container">
    <div class="section-header">
        <span>Academic Excellence</span>
        <h3>Leading Schools & Disciplines</h3>
    </div>

    <div class="grid-3">
        <div class="info-card">
            <div class="card-icon">💻</div>
            <h4>School of Computing & AI</h4>
            <p>Pioneering education in machine learning, distributed architectures, algorithms, and cybersecurity systems.</p>
            <a href="?page=programs" class="card-link">View Degree Requirements →</a>
        </div>
        <div class="info-card">
            <div class="card-icon">⚙️</div>
            <h4>College of Engineering</h4>
            <p>Accredited programs across mechanical, electrical, and biomedical engineering with advanced fabrication labs.</p>
            <a href="?page=programs" class="card-link">View Degree Requirements →</a>
        </div>
        <div class="info-card">
            <div class="card-icon">📊</div>
            <h4>Center for Data Science</h4>
            <p>Interdisciplinary training combining mathematical statistics, big-data pipelines, and predictive analytics.</p>
            <a href="?page=programs" class="card-link">View Degree Requirements →</a>
        </div>
    </div>

    <div class="callout-box">
        <div class="callout-text">
            <h4>Institutional Prospectus & Guidelines (2026-2027)</h4>
            <p>Review admissions policies, portfolio standards, and financial sponsorship guidelines.</p>
        </div>
        <div>
            <a href="uploads/academic_prospectus_2026.pdf" target="_blank" class="btn-hero-primary" style="font-size:13px;padding:10px 20px;">Download Prospectus (PDF)</a>
        </div>
    </div>
</main>

<?php elseif ($page === 'programs'): ?>

<section class="hero" style="padding: 50px 24px;">
    <div class="hero-inner">
        <h2>Undergraduate & Graduate Programs</h2>
        <p>Explore rigorous degree offerings led by world-renowned faculty researchers.</p>
    </div>
</section>

<main class="container">
    <div class="grid-3">
        <div class="info-card">
            <h4>B.Sc. Computer Science & AI</h4>
            <p>Core curriculum covering systems software, neural networks, algorithmic complexity, and cloud architecture.</p>
            <span style="font-size:12px;color:#64748b;font-weight:600;">Credits: 128 | Duration: 4 Years</span>
        </div>
        <div class="info-card">
            <h4>B.Eng. Mechanical Engineering</h4>
            <p>Comprehensive foundation in thermodynamics, fluid dynamics, robotics, and aerospace structures.</p>
            <span style="font-size:12px;color:#64748b;font-weight:600;">Credits: 132 | Duration: 4 Years</span>
        </div>
        <div class="info-card">
            <h4>B.Sc. Data Science & Analytics</h4>
            <p>Statistical computing, exploratory data mining, machine learning pipelines, and quantitative decision science.</p>
            <span style="font-size:12px;color:#64748b;font-weight:600;">Credits: 124 | Duration: 4 Years</span>
        </div>
        <div class="info-card">
            <h4>M.Sc. Cybersecurity & Trust</h4>
            <p>Advanced graduate study in cryptosystems, threat modeling, binary exploitation defense, and cloud security.</p>
            <span style="font-size:12px;color:#64748b;font-weight:600;">Credits: 36 | Duration: 2 Years</span>
        </div>
        <div class="info-card">
            <h4>B.A. Digital Media & Interactive Design</h4>
            <p>Interactive web interfaces, 3D digital visualization, user experience architecture, and creative computing.</p>
            <span style="font-size:12px;color:#64748b;font-weight:600;">Credits: 120 | Duration: 4 Years</span>
        </div>
        <div class="info-card">
            <h4>MBA Technology Leadership</h4>
            <p>Executive management combining product commercialization, venture finance, and enterprise governance.</p>
            <span style="font-size:12px;color:#64748b;font-weight:600;">Credits: 48 | Duration: 2 Years</span>
        </div>
    </div>
</main>

<?php elseif ($page === 'admissions'): ?>

<section class="hero" style="padding: 50px 24px;">
    <div class="hero-inner">
        <h2>Admissions & Eligibility Criteria</h2>
        <p>Step-by-step guidance on application requirements, deadlines, and international visa protocols.</p>
    </div>
</section>

<main class="container">
    <div class="grid-3">
        <div class="info-card">
            <h4>1. Academic Transcripts</h4>
            <p>Official secondary school transcripts or accredited undergraduate degree equivalent with a minimum 3.0 cumulative GPA.</p>
        </div>
        <div class="info-card">
            <h4>2. Standardized Testing</h4>
            <p>SAT or ACT scores recommended for undergraduate applicants. GRE/GMAT scores considered for graduate programs.</p>
        </div>
        <div class="info-card">
            <h4>3. Letters of Recommendation</h4>
            <p>Two confidential academic or professional recommendation letters submitted directly by endorsers.</p>
        </div>
    </div>

    <div class="callout-box" style="margin-top: 40px;">
        <div class="callout-text">
            <h4>Upcoming Application Milestones</h4>
            <p>Early Action: November 30, 2026 · Regular Decision: January 15, 2027 · Financial Aid Priority: February 01, 2027</p>
        </div>
        <a href="?page=apply" class="btn-hero-primary" style="font-size:13px;padding:10px 20px;">Start Application</a>
    </div>
</main>

<?php elseif ($page === 'tuition'): ?>

<section class="hero" style="padding: 50px 24px;">
    <div class="hero-inner">
        <h2>Tuition, Grants & Financial Aid</h2>
        <p>We are committed to ensuring qualified candidates can access an exceptional education regardless of background.</p>
    </div>
</section>

<main class="container">
    <div class="grid-3">
        <div class="info-card">
            <h4>Undergraduate Tuition</h4>
            <p><strong>$42,500 / academic year</strong><br>Covers full instructional costs, research laboratory access, and university health services.</p>
        </div>
        <div class="info-card">
            <h4>Need-Based Institutional Grants</h4>
            <p>Over 82% of incoming students receive institutional tuition waivers or need-based grants based on CSS Profile assessments.</p>
        </div>
        <div class="info-card">
            <h4>Presidential Merit Fellowships</h4>
            <p>Full-tuition scholarships awarded automatically to candidates in the top 3% percentile of the admission pool.</p>
        </div>
    </div>
</main>

<?php elseif ($page === 'apply'): ?>

<section class="hero" style="padding: 50px 24px;">
    <div class="hero-inner">
        <h2>Undergraduate & Graduate Application Form</h2>
        <p>Submit your candidate profile for review by the admissions committee.</p>
    </div>
</section>

<main class="container">
    <div class="form-wrapper">
        <?php if ($applied): ?>
            <div class="alert-success">
                <strong>Application Filed Successfully!</strong><br>
                Your application docket reference number is <strong><?= htmlspecialchars($ref_id) ?></strong>.
                Our committee will review your dossier and contact your listed email with portal tracking credentials.
            </div>
        <?php endif; ?>

        <form method="POST" action="?page=apply">
            <div class="form-row">
                <div class="form-group">
                    <label for="full_name">Legal Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="e.g. Alex Morgan">
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth *</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required placeholder="alex.morgan@example.com">
                </div>
                <div class="form-group">
                    <label for="phone">Contact Phone *</label>
                    <input type="tel" id="phone" name="phone" required placeholder="+1 (555) 019-2834">
                </div>
            </div>

            <div class="form-group">
                <label for="program">Intended Academic Major *</label>
                <select id="program" name="program">
                    <option value="cs">B.Sc. Computer Science & AI</option>
                    <option value="mech">B.Eng. Mechanical Engineering</option>
                    <option value="ds">B.Sc. Data Science & Analytics</option>
                    <option value="cyber">M.Sc. Cybersecurity & Trust</option>
                    <option value="design">B.A. Digital Media & Design</option>
                    <option value="mba">MBA Technology Leadership</option>
                </select>
            </div>

            <div class="form-group">
                <label for="gpa">Cumulative High School / Undergraduate GPA</label>
                <input type="text" id="gpa" name="gpa" placeholder="e.g. 3.85">
            </div>

            <button type="submit" class="btn-submit-app">Submit Application Dossier</button>
        </form>
    </div>
</main>

<?php elseif ($page === 'contact'): ?>

<section class="hero" style="padding: 50px 24px;">
    <div class="hero-inner">
        <h2>Contact the Admissions Committee</h2>
        <p>Our counselors are available to guide you through application requirements and campus tours.</p>
    </div>
</section>

<main class="container">
    <div class="grid-3">
        <div class="info-card">
            <h4>Admissions Office</h4>
            <p>
                Riverview Institute of Technology<br>
                Hall of Admissions, Suite 100<br>
                450 Academic Way, Cambridge, MA 02138
            </p>
        </div>
        <div class="info-card">
            <h4>Direct Contacts</h4>
            <p>
                Email: admissions@riverview.test<br>
                Phone: +1 (555) 019-2834<br>
                Office Hours: Mon–Fri, 9:00 AM – 5:00 PM EST
            </p>
        </div>
        <div class="info-card">
            <h4>Virtual Office Hours</h4>
            <p>
                Join our bi-weekly admissions Q&A webinar every Tuesday and Thursday at 2:00 PM EST.
            </p>
        </div>
    </div>
</main>

<?php endif; ?>

<footer>
    <div class="footer-inner">
        <div class="footer-col">
            <h5>Riverview Institute of Technology</h5>
            <p>
                A chartered independent research institution dedicated to the advancement of science,
                engineering, computing, and humane technology for the public good.
            </p>
        </div>
        <div class="footer-col">
            <h5>Admissions</h5>
            <ul>
                <li><a href="?page=programs">Undergraduate Majors</a></li>
                <li><a href="?page=programs">Graduate Degrees</a></li>
                <li><a href="?page=admissions">Deadlines & Dates</a></li>
                <li><a href="?page=tuition">Tuition & Financial Aid</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h5>Campus Life</h5>
            <ul>
                <li><a href="?page=home">Campus Tours</a></li>
                <li><a href="?page=home">Residential Housing</a></li>
                <li><a href="?page=home">Student Research Labs</a></li>
                <li><a href="?page=contact">International Student Center</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h5>Faculty & Administration</h5>
            <ul>
                <li><a href="admin/">Admissions Staff Portal</a></li>
                <li><a href="?page=contact">Academic Departments</a></li>
                <li><a href="?page=contact">Registrar Office</a></li>
                <li><a href="?page=contact">Directory Information</a></li>
            </ul>
        </div>
    </div>

    <div class="footer-bottom">
        <div>
            © 2026 Riverview Institute of Technology. All Rights Reserved. Accredited by NECHE.
        </div>
        <div>
            <a href="?page=home">Privacy Policy</a>
            <a href="?page=home">Terms of Enrollment</a>
            <a href="admin/">Staff Login</a>
        </div>
    </div>
</footer>

</body>
</html>
