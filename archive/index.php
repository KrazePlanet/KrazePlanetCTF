<?php
// PixelVault — Creative Agency Portfolio
// LFI vulnerability: ?file= parameter allows path traversal
if (isset($_GET['file'])) {
    $file = $_GET['file'];

    // If the file parameter is an external URL, show full-screen image viewer
    if (strpos($file, 'http://') === 0 || strpos($file, 'https://') === 0) {
        $imgUrl = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PixelVault — Project Preview</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: "Inter", sans-serif;
        }
        .viewer-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 2rem;
            background: rgba(255,255,255,0.04);
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .viewer-nav a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .viewer-nav a:hover { color: #fff; }
        .viewer-nav .brand {
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            margin-right: auto;
        }
        .viewer-nav .brand span { color: #6366f1; }
        .viewer-body {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .viewer-body img {
            max-width: 100%;
            max-height: calc(100vh - 120px);
            border-radius: 12px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            object-fit: contain;
        }
        .viewer-footer {
            text-align: center;
            padding: 1rem;
            color: #475569;
            font-size: 0.8rem;
        }
        .viewer-footer code {
            background: rgba(255,255,255,0.05);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <nav class="viewer-nav">
        <span class="brand">Pixel<span>Vault</span></span>
        <a href="index.php">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Back to Portfolio
        </a>
    </nav>
    <div class="viewer-body">
        <img src="' . $imgUrl . '?w=1600&q=90" alt="Project preview">
    </div>
    <div class="viewer-footer">Viewing: <code>' . $imgUrl . '</code></div>
</body>
</html>';
        exit;
    }

    // Local file inclusion (vulnerable — no sanitization)
    $basePath = __DIR__ . '/images/';
    $filePath = $basePath . $file;

    if (file_exists($filePath)) {
        $mimeType = mime_content_type($filePath);
        header('Content-Type: ' . $mimeType);
        readfile($filePath);
    } else {
        http_response_code(404);
        die("File not found");
    }
    exit;
}

// Page routing — real-world LFI parameter names commonly found in production apps
$section = 'portfolio';
if (isset($_GET['page']))     $section = $_GET['page'];     // ?page=../../etc/passwd
if (isset($_GET['include']))  $section = $_GET['include'];  // ?include=../../etc/passwd
if (isset($_GET['template'])) $section = $_GET['template']; // ?template=../../etc/passwd
if (isset($_GET['doc']))      $section = $_GET['doc'];      // ?doc=../../etc/passwd
if (isset($_GET['view']))     $section = $_GET['view'];     // ?view=../../etc/passwd
$section = in_array($section, ['portfolio','services','about','contact','quote']) ? $section : 'portfolio';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PixelVault — Creative Agency Portfolio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }

        /* ── Navigation ── */
        .navbar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(226,232,240,0.6);
            padding: 0.875rem 0;
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.35rem;
            letter-spacing: -0.02em;
            color: #0f172a !important;
        }
        .navbar-brand span { color: #6366f1; }
        .nav-link {
            font-weight: 500;
            font-size: 0.9rem;
            color: #475569 !important;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-link:hover { background: #f1f5f9; color: #0f172a !important; }

        /* ── Hero ── */
        .hero {
            padding: 5rem 0 3.5rem;
            text-align: center;
            background: linear-gradient(180deg, #eef2ff 0%, #f8fafc 100%);
        }
        .hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            color: #0f172a;
        }
        .hero h1 span { color: #6366f1; }
        .hero p {
            font-size: 1.1rem;
            color: #64748b;
            max-width: 560px;
            margin: 1.25rem auto 0;
            line-height: 1.7;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.35rem 1rem;
            border-radius: 100px;
            margin-bottom: 1.25rem;
        }

        /* ── Section ── */
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }
        .section-sub {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        /* ── Portfolio Grid ── */
        .portfolio-item {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        .portfolio-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.08);
        }
        .portfolio-item img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            display: block;
            transition: transform 0.5s ease;
        }
        .portfolio-item:hover img { transform: scale(1.04); }
        .portfolio-overlay {
            position: absolute;
            bottom: 0;
            left: 0; right: 0;
            padding: 1.25rem 1.25rem 1rem;
            background: linear-gradient(transparent, rgba(15,23,42,0.85));
            color: #fff;
        }
        .portfolio-overlay h5 {
            font-weight: 700;
            font-size: 1rem;
            margin: 0;
        }
        .portfolio-overlay p {
            font-size: 0.8rem;
            margin: 0.2rem 0 0;
            opacity: 0.8;
        }
        .portfolio-tag {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(4px);
            color: #1e293b;
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.25rem 0.7rem;
            border-radius: 100px;
        }

        /* ── Stats ── */
        .stats-row {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            padding: 2.5rem 1.5rem;
            margin: 3rem 0;
        }
        .stat-item { text-align: center; }
        .stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: #6366f1;
        }
        .stat-label {
            font-size: 0.85rem;
            color: #94a3b8;
            font-weight: 500;
            margin-top: 0.25rem;
        }

        /* ── CTA ── */
        .cta-section {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            border-radius: 20px;
            padding: 3.5rem 2rem;
            text-align: center;
            color: #fff;
            margin: 3rem 0;
        }
        .cta-section h2 {
            font-weight: 700;
            font-size: 1.75rem;
            letter-spacing: -0.02em;
        }
        .cta-section p {
            opacity: 0.85;
            margin: 0.75rem auto 1.5rem;
            max-width: 480px;
        }
        .btn-cta {
            background: #fff;
            color: #4f46e5;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 100px;
            border: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .btn-cta:hover { background: #f1f5f9; color: #4338ca; transform: translateY(-1px); }

        /* ── Footer ── */
        footer {
            text-align: center;
            padding: 2rem 0 1.5rem;
            color: #94a3b8;
            font-size: 0.85rem;
            border-top: 1px solid #e2e8f0;
        }

        /* ── Nav Active ── */
        .nav-active { background: #f1f5f9 !important; color: #0f172a !important; font-weight: 600 !important; }

        /* ── Service Cards ── */
        .service-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04); transition:transform 0.3s,box-shadow 0.3s; }
        .service-card:hover { transform:translateY(-4px); box-shadow:0 12px 32px rgba(0,0,0,0.08); }
        .service-card img { width:100%; height:200px; object-fit:cover; display:block; }
        .service-card-body { padding:1.25rem; }
        .service-card-body h5 { font-weight:700; font-size:1rem; margin-bottom:0.5rem; }
        .service-card-body p { color:#64748b; font-size:0.875rem; line-height:1.6; margin:0; }
        .service-price { display:inline-block; margin-top:0.75rem; font-size:0.8rem; font-weight:600; color:#6366f1; }

        /* ── Team Cards ── */
        .team-card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04); text-align:center; padding-bottom:1.5rem; }
        .team-card img { width:100%; height:220px; object-fit:cover; object-position:top; display:block; }
        .team-card h5 { font-weight:700; font-size:1rem; margin:1rem 0 0.25rem; }
        .team-card p { color:#6366f1; font-size:0.85rem; font-weight:500; margin:0; }

        /* ── Forms ── */
        .form-section { background:#fff; border-radius:20px; box-shadow:0 1px 3px rgba(0,0,0,0.04); padding:2.5rem; }
        .form-label { font-weight:600; font-size:0.875rem; color:#374151; margin-bottom:0.4rem; display:block; }
        .form-control, .form-select { border:1.5px solid #e2e8f0; border-radius:10px; padding:0.65rem 1rem; font-size:0.9rem; width:100%; transition:border-color 0.2s,box-shadow 0.2s; background:#fff; }
        .form-control:focus, .form-select:focus { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,0.1); outline:none; }
        textarea.form-control { resize:vertical; }
        .btn-submit { background:#6366f1; color:#fff; font-weight:600; padding:0.75rem 2rem; border-radius:100px; border:none; font-size:0.9rem; cursor:pointer; transition:background 0.2s,transform 0.2s; }
        .btn-submit:hover { background:#4f46e5; transform:translateY(-1px); }

        /* ── Contact Info ── */
        .contact-info-item { display:flex; align-items:flex-start; gap:1rem; margin-bottom:1.5rem; }
        .contact-info-icon { width:42px; height:42px; background:#eef2ff; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; color:#6366f1; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .hero h1 { font-size: 2rem; }
            .portfolio-item img { height: 200px; }
        }
    </style>
</head>
<body>

    <!-- ═══ Navigation ═══ -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="?page=portfolio">Pixel<span>Vault</span></a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                    <li class="nav-item"><a class="nav-link <?= $section==='portfolio'?'nav-active':'' ?>" href="?page=portfolio">Portfolio</a></li>
                    <li class="nav-item"><a class="nav-link <?= $section==='services'?'nav-active':'' ?>" href="?include=services">Services</a></li>
                    <li class="nav-item"><a class="nav-link <?= $section==='about'?'nav-active':'' ?>" href="?template=about">About</a></li>
                    <li class="nav-item"><a class="nav-link <?= $section==='contact'?'nav-active':'' ?>" href="?doc=contact">Contact</a></li>
                    <li class="nav-item ms-lg-2"><a class="nav-link <?= $section==='quote'?'nav-active':'' ?>" href="?view=quote" style="background:#6366f1;color:#fff!important;padding:0.5rem 1.25rem!important;border-radius:100px;font-weight:600;">Get a Quote</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ═══ Hero (dynamic) ═══ -->
    <?php
    $heroes = [
        'portfolio' => ['badge'=>'Featured on Awwwards &middot; 2026',    'h1'=>'We build brands that<br><span>leave a mark.</span>',      'p'=>'PixelVault is a creative agency specializing in digital design, brand identity, and visual storytelling for ambitious companies worldwide.'],
        'services'  => ['badge'=>'What We Do',                             'h1'=>'Full-Service<br><span>Creative Solutions</span>',          'p'=>'From brand identity to interactive web experiences, we offer end-to-end creative services tailored to your goals.'],
        'about'     => ['badge'=>'Our Story',                              'h1'=>'A team built on<br><span>craft &amp; curiosity.</span>',   'p'=>'Founded in 2014, PixelVault brings together designers, strategists, and developers who believe great work changes businesses.'],
        'contact'   => ['badge'=>'Get In Touch',                           'h1'=>'Let\'s talk about<br><span>your next project.</span>',     'p'=>'We\'re always looking for new challenges. Reach out and let\'s see how we can help you grow.'],
        'quote'     => ['badge'=>'Start a Project',                        'h1'=>'Tell us about<br><span>your vision.</span>',               'p'=>'Fill out the form below and we\'ll get back to you within 24 hours with a tailored proposal.'],
    ];
    $h = $heroes[$section];
    ?>
    <section class="hero">
        <div class="container">
            <span class="hero-badge">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                <?= $h['badge'] ?>
            </span>
            <h1><?= $h['h1'] ?></h1>
            <p><?= $h['p'] ?></p>
        </div>
    </section>

    <!-- ═══ Main Content ═══ -->
    <div class="container" style="margin-top:-0.5rem;">

    <?php if ($section === 'portfolio'): ?>

        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-4">
            <div>
                <h2 class="section-title">Selected Work</h2>
                <p class="section-sub mb-0">A glimpse at our latest projects across industries.</p>
            </div>
            <a href="?page=portfolio" style="color:#6366f1;font-weight:600;font-size:0.9rem;text-decoration:none;">View all projects &rarr;</a>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <a href="?file=https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe" class="portfolio-item" style="text-decoration:none;color:inherit;">
                    <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&q=80" alt="Lumina Cosmetics">
                    <span class="portfolio-tag">Branding</span>
                    <div class="portfolio-overlay"><h5>Lumina Cosmetics</h5><p>Identity &amp; packaging redesign</p></div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="?file=https://images.unsplash.com/photo-1557683316-973673baf926" class="portfolio-item" style="text-decoration:none;color:inherit;">
                    <img src="https://images.unsplash.com/photo-1557683316-973673baf926?w=600&q=80" alt="Drift Studios">
                    <span class="portfolio-tag">Web Design</span>
                    <div class="portfolio-overlay"><h5>Drift Studios</h5><p>Full website &amp; CMS integration</p></div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="?file=https://images.unsplash.com/photo-1506905925346-21bda4d32df4" class="portfolio-item" style="text-decoration:none;color:inherit;">
                    <img src="https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=600&q=80" alt="Terra Outdoors">
                    <span class="portfolio-tag">Campaign</span>
                    <div class="portfolio-overlay"><h5>Terra Outdoors</h5><p>Seasonal campaign &amp; content</p></div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="?file=https://images.unsplash.com/photo-1558591710-4b4a1ae0f04d" class="portfolio-item" style="text-decoration:none;color:inherit;">
                    <img src="https://images.unsplash.com/photo-1558591710-4b4a1ae0f04d?w=600&q=80" alt="Nexus Dashboard">
                    <span class="portfolio-tag">UI/UX</span>
                    <div class="portfolio-overlay"><h5>Nexus Dashboard</h5><p>Analytics platform interface</p></div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="?file=https://images.unsplash.com/photo-1586495777744-4e6232bf5e69" class="portfolio-item" style="text-decoration:none;color:inherit;">
                    <img src="https://images.unsplash.com/photo-1586495777744-4e6232bf5e69?w=600&q=80" alt="Solara Skincare">
                    <span class="portfolio-tag">Packaging</span>
                    <div class="portfolio-overlay"><h5>Solara Skincare</h5><p>Sustainable packaging system</p></div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="?file=https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d" class="portfolio-item" style="text-decoration:none;color:inherit;">
                    <img src="https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?w=600&q=80" alt="Apex Sports">
                    <span class="portfolio-tag">Motion</span>
                    <div class="portfolio-overlay"><h5>Apex Sports</h5><p>Launch video &amp; motion identity</p></div>
                </a>
            </div>
        </div>
        <div class="stats-row row g-3">
            <div class="col-4 stat-item"><div class="stat-number">12+</div><div class="stat-label">Years in business</div></div>
            <div class="col-4 stat-item"><div class="stat-number">280+</div><div class="stat-label">Projects delivered</div></div>
            <div class="col-4 stat-item"><div class="stat-number">98%</div><div class="stat-label">Client satisfaction</div></div>
        </div>
        <div class="cta-section">
            <h2>Ready to elevate your brand?</h2>
            <p>We partner with founders and marketing leaders to create design that drives results.</p>
            <a href="?view=quote" class="btn-cta" style="display:inline-block;text-decoration:none;">Start Your Project</a>
        </div>

    <?php elseif ($section === 'services'): ?>

        <div class="mb-4">
            <h2 class="section-title">Our Services</h2>
            <p class="section-sub">Everything you need to build a world-class brand.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1561070791-2526d30994b5?w=600&q=80" alt="Brand Identity">
                    <div class="service-card-body"><h5>Brand Identity</h5><p>Logos, color systems, and brand guidelines that define who you are and what you stand for.</p><span class="service-price">From $4,500</span></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1547658719-da2b51169166?w=600&q=80" alt="Web Design">
                    <div class="service-card-body"><h5>Web Design &amp; Dev</h5><p>Custom websites built for speed, accessibility, and conversion — from landing pages to full apps.</p><span class="service-price">From $8,000</span></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1574717024653-61fd2cf4d44d?w=600&q=80" alt="Motion">
                    <div class="service-card-body"><h5>Motion &amp; Video</h5><p>Brand films, product reels, and animated explainers that bring your story to life.</p><span class="service-price">From $3,200</span></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1586495777744-4e6232bf5e69?w=600&q=80" alt="Packaging">
                    <div class="service-card-body"><h5>Print &amp; Packaging</h5><p>Retail packaging, stationery, and print materials designed to stand out on any shelf.</p><span class="service-price">From $2,800</span></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?w=600&q=80" alt="Strategy">
                    <div class="service-card-body"><h5>Brand Strategy</h5><p>Positioning, messaging, and audience research that forms the foundation of everything we build.</p><span class="service-price">From $5,000</span></div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="service-card">
                    <img src="https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=600&q=80" alt="Photography">
                    <div class="service-card-body"><h5>Photography &amp; Art Direction</h5><p>Studio and lifestyle photography with full creative direction for campaigns and editorial.</p><span class="service-price">From $2,400</span></div>
                </div>
            </div>
        </div>
        <div class="cta-section" style="margin-top:3rem;">
            <h2>Not sure which service you need?</h2>
            <p>Book a free 30-minute discovery call and we'll figure out the right scope together.</p>
            <a href="?view=quote" class="btn-cta" style="display:inline-block;text-decoration:none;">Book a Call</a>
        </div>

    <?php elseif ($section === 'about'): ?>

        <div class="row g-5 mb-5 align-items-center">
            <div class="col-lg-6">
                <h2 class="section-title">Hello, we're PixelVault.</h2>
                <p style="color:#64748b;line-height:1.8;font-size:0.95rem;margin-bottom:1rem;">Founded in Los Angeles in 2014, we started as a two-person studio obsessed with typography and got quickly addicted to solving hard brand problems for ambitious clients.</p>
                <p style="color:#64748b;line-height:1.8;font-size:0.95rem;margin-bottom:1.5rem;">Today we're a 22-person team across LA, Amsterdam, and Singapore, working with startups, Fortune 500s, and everyone in between.</p>
                <div class="row g-3">
                    <div class="col-4"><div style="background:#f8fafc;border-radius:12px;padding:1rem;text-align:center;"><div class="stat-number">22</div><div class="stat-label">Team members</div></div></div>
                    <div class="col-4"><div style="background:#f8fafc;border-radius:12px;padding:1rem;text-align:center;"><div class="stat-number">3</div><div class="stat-label">Global offices</div></div></div>
                    <div class="col-4"><div style="background:#f8fafc;border-radius:12px;padding:1rem;text-align:center;"><div class="stat-number">42</div><div class="stat-label">Awards won</div></div></div>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&q=80" alt="Team" style="width:100%;border-radius:20px;box-shadow:0 12px 32px rgba(0,0,0,0.08);object-fit:cover;height:360px;display:block;">
            </div>
        </div>
        <h2 class="section-title mb-1">Meet the team</h2>
        <p class="section-sub">The people behind the pixels.</p>
        <div class="row g-4">
            <div class="col-6 col-md-3"><div class="team-card"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&q=80" alt="Jordan Miles"><h5>Jordan Miles</h5><p>Founder &amp; CEO</p></div></div>
            <div class="col-6 col-md-3"><div class="team-card"><img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=400&q=80" alt="Priya Sharma"><h5>Priya Sharma</h5><p>Creative Director</p></div></div>
            <div class="col-6 col-md-3"><div class="team-card"><img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&q=80" alt="Leo Vanderbeck"><h5>Leo Vanderbeck</h5><p>Lead Designer</p></div></div>
            <div class="col-6 col-md-3"><div class="team-card"><img src="https://images.unsplash.com/photo-1517841905240-472988babdf9?w=400&q=80" alt="Maya Chen"><h5>Maya Chen</h5><p>Head of Strategy</p></div></div>
        </div>

    <?php elseif ($section === 'contact'): ?>

        <div class="row g-5">
            <div class="col-lg-7">
                <h2 class="section-title mb-1">Send us a message</h2>
                <p class="section-sub">We read every message and respond within one business day.</p>
                <div class="form-section">
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">First name</label><input type="text" class="form-control" placeholder="Alex"></div>
                            <div class="col-md-6"><label class="form-label">Last name</label><input type="text" class="form-control" placeholder="Rivera"></div>
                            <div class="col-12"><label class="form-label">Email address</label><input type="email" class="form-control" placeholder="alex@company.com"></div>
                            <div class="col-12"><label class="form-label">Subject</label><input type="text" class="form-control" placeholder="Project inquiry"></div>
                            <div class="col-12"><label class="form-label">Message</label><textarea class="form-control" rows="5" placeholder="Tell us about your project..."></textarea></div>
                            <div class="col-12"><button type="submit" class="btn-submit">Send Message</button></div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <h2 class="section-title mb-1">Find us</h2>
                <p class="section-sub">Offices in three time zones.</p>
                <div class="contact-info-item">
                    <div class="contact-info-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                    <div><strong style="display:block;font-size:.9rem;">Los Angeles (HQ)</strong><span style="color:#64748b;font-size:.85rem;">1234 Sunset Blvd, Suite 500, CA 90028</span></div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                    <div><strong style="display:block;font-size:.9rem;">Amsterdam</strong><span style="color:#64748b;font-size:.85rem;">Keizersgracht 482, 1017 EG Amsterdam</span></div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 8.81 19.79 19.79 0 01.01 2.22 2 2 0 012 .01h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg></div>
                    <div><strong style="display:block;font-size:.9rem;">Phone</strong><span style="color:#64748b;font-size:.85rem;">+1 (323) 555-0192</span></div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></div>
                    <div><strong style="display:block;font-size:.9rem;">Email</strong><span style="color:#64748b;font-size:.85rem;">hello@pixelvault.agency</span></div>
                </div>
                <img src="https://images.unsplash.com/photo-1497366858526-0766ad8ffdb?w=600&q=80" alt="Office" style="width:100%;border-radius:16px;margin-top:0.5rem;object-fit:cover;height:200px;display:block;">
            </div>
        </div>

    <?php elseif ($section === 'quote'): ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="section-title mb-1">Request a Quote</h2>
                <p class="section-sub">Tell us about your project and we'll send a detailed proposal within 24 hours.</p>
                <div class="form-section">
                    <form>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Full name</label><input type="text" class="form-control" placeholder="Alex Rivera"></div>
                            <div class="col-md-6"><label class="form-label">Company</label><input type="text" class="form-control" placeholder="Acme Inc."></div>
                            <div class="col-12"><label class="form-label">Email address</label><input type="email" class="form-control" placeholder="alex@company.com"></div>
                            <div class="col-md-6"><label class="form-label">Service needed</label><select class="form-select"><option>Brand Identity</option><option>Web Design &amp; Dev</option><option>Motion &amp; Video</option><option>Print &amp; Packaging</option><option>Brand Strategy</option><option>Photography</option><option>Full Rebrand</option></select></div>
                            <div class="col-md-6"><label class="form-label">Budget range</label><select class="form-select"><option>$2,000 – $5,000</option><option>$5,000 – $15,000</option><option>$15,000 – $50,000</option><option>$50,000+</option></select></div>
                            <div class="col-12"><label class="form-label">Project timeline</label><select class="form-select"><option>ASAP (rush fee applies)</option><option>1 – 2 months</option><option>3 – 4 months</option><option>6+ months</option><option>Flexible</option></select></div>
                            <div class="col-12"><label class="form-label">Project description</label><textarea class="form-control" rows="5" placeholder="Describe your project, goals, target audience, and any inspiration..."></textarea></div>
                            <div class="col-12"><button type="submit" class="btn-submit">Submit Request</button><p style="color:#94a3b8;font-size:0.8rem;margin-top:0.75rem;margin-bottom:0;">We'll respond within 24 hours. No spam, ever.</p></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>

    </div><!-- /container -->

    <!-- ═══ Footer ═══ -->
    <footer>
        <div class="container">
            &copy; 2026 PixelVault Creative Agency. All rights reserved.<br>
            <span style="font-size:0.75rem;">Built with passion in Los Angeles &middot; Amsterdam &middot; Singapore</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
