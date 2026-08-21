<?php
// Multi-language Blog CMS — Language Pack Loader
$lang = $_GET['lang'] ?? 'languages/en.php';

// Load the selected language pack from disk (Vulnerable to LFI)
if (!empty($lang)) {
    @include($lang);
}

// Fallback defaults if language pack failed to load
if (empty($L)) {
    $L = [
        'dir'        => 'ltr',
        'site_title' => 'NexusCMS Tech & Insights',
        'nav_home'   => 'Home',
        'nav_articles'=> 'Articles',
        'nav_about'  => 'About CMS',
        'hero_title' => 'Insights on Software Engineering & Web Architecture',
        'hero_subtitle' => 'Explore the latest articles on system design, security, and cloud scalability.',
        'read_more'  => 'Read Full Article',
        'post1_title'=> 'Understanding Modern CMS Localization Architectures',
        'post1_desc' => 'How content management systems load external language packs and common security pitfalls in i18n implementation.',
        'post2_title'=> 'Building High-Performance PHP Microservices',
        'post2_desc' => 'Best practices for caching, routing, and database optimization in production environments.',
        'footer'     => '© 2026 NexusCMS Portal. All rights reserved.'
    ];
}

$rtl = ($L['dir'] === 'rtl');
?>
<!doctype html>
<html lang="<?php echo substr(basename($lang, '.php'), 0, 2); ?>" dir="<?php echo $L['dir']; ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($L['site_title']); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&family=Noto+Sans+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" />

  <style>
    :root {
      --body-bg:     #0c111d;
      --surface-1:   #111827;
      --surface-2:   #1f2937;
      --border:      rgba(255,255,255,0.09);
      --accent:      #38bdf8;
      --accent-2:    #34d399;
      --text-main:   #f1f5f9;
      --text-muted:  #94a3b8;
    }

    body {
      background: var(--body-bg);
      color: var(--text-main);
      font-family: <?php echo $rtl ? "'Noto Sans Arabic', " : ''; ?>'Inter', system-ui, sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── Navbar ── */
    .navbar {
      background: rgba(17,24,39,0.97) !important;
      border-bottom: 1px solid var(--border);
      backdrop-filter: blur(18px);
      padding: .85rem 0;
    }
    .navbar-brand {
      font-weight: 800;
      font-size: 1.35rem;
      background: linear-gradient(90deg,#38bdf8,#34d399);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .nav-link { color: var(--text-muted) !important; font-weight: 600; border-radius: 8px; transition: all .2s; }
    .nav-link:hover, .nav-link.active { color: #fff !important; background: rgba(255,255,255,.08); }

    /* ── Language Switcher ── */
    .lang-switcher {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      align-items: center;
    }
    .lang-btn {
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.14);
      color: var(--text-muted);
      font-size: .78rem;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 20px;
      text-decoration: none;
      transition: all .2s;
    }
    .lang-btn:hover, .lang-btn.active {
      background: rgba(56,189,248,.18);
      border-color: var(--accent);
      color: var(--accent);
    }

    /* ── Hero ── */
    .hero-section {
      background: radial-gradient(ellipse at top, rgba(56,189,248,.1), transparent 65%),
                  radial-gradient(ellipse at bottom right, rgba(52,211,153,.07), transparent 55%);
      padding: 4rem 0 3rem;
      border-bottom: 1px solid var(--border);
    }
    .hero-badge {
      display: inline-block;
      background: rgba(56,189,248,.12);
      border: 1px solid rgba(56,189,248,.3);
      color: var(--accent);
      font-size: .78rem;
      font-weight: 700;
      letter-spacing: .06em;
      text-transform: uppercase;
      padding: 4px 14px;
      border-radius: 20px;
      margin-bottom: 1rem;
    }
    .hero-title {
      font-weight: 800;
      font-size: 2.5rem;
      line-height: 1.2;
      letter-spacing: -.5px;
      background: linear-gradient(135deg, #ffffff 40%, #38bdf8 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .hero-subtitle {
      color: var(--text-muted);
      font-size: 1.1rem;
      max-width: 620px;
    }

    /* ── Article Cards ── */
    .article-card {
      background: linear-gradient(135deg, rgba(31,41,55,.95), rgba(17,24,39,.95));
      border: 1px solid var(--border);
      border-left: 4px solid var(--accent-2);
      border-radius: 14px;
      padding: 1.75rem;
      box-shadow: 0 6px 22px rgba(0,0,0,.3), inset 0 1px 0 rgba(255,255,255,.06);
      transition: all .25s cubic-bezier(.4,0,.2,1);
    }
    .article-card:hover {
      transform: translateY(-3px);
      border-left-color: var(--accent);
      box-shadow: 0 12px 30px rgba(0,0,0,.45), 0 0 22px rgba(56,189,248,.15);
    }
    .article-cat {
      font-size: .72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .07em;
      padding: 3px 10px;
      border-radius: 6px;
      background: rgba(52,211,153,.15);
      color: var(--accent-2);
      border: 1px solid rgba(52,211,153,.25);
    }
    .article-title {
      font-weight: 700;
      font-size: 1.2rem;
      color: #fff;
      margin: .65rem 0 .5rem;
      line-height: 1.4;
    }
    .article-desc {
      color: var(--text-muted);
      font-size: .92rem;
      line-height: 1.65;
    }
    .btn-read {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(56,189,248,.12);
      border: 1px solid rgba(56,189,248,.3);
      color: var(--accent);
      font-weight: 700;
      font-size: .84rem;
      padding: 8px 18px;
      border-radius: 10px;
      text-decoration: none;
      transition: all .2s;
      margin-top: .9rem;
    }
    .btn-read:hover {
      background: rgba(56,189,248,.22);
      color: #fff;
      transform: translateX(<?php echo $rtl ? '-4px' : '4px'; ?>);
    }

    /* ── Sidebar ── */
    .sidebar-card {
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 1.4rem;
    }
    .sidebar-heading {
      font-size: .75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .09em;
      color: var(--text-muted);
      margin-bottom: 1rem;
    }
    .tag-pill {
      display: inline-block;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.12);
      color: var(--text-muted);
      font-size: .78rem;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 20px;
      margin: 3px;
    }

    /* ── Footer ── */
    footer {
      margin-top: auto;
      padding: 1.5rem 0;
      background: var(--surface-1);
      border-top: 1px solid var(--border);
      color: var(--text-muted);
      font-size: .875rem;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="?lang=languages/en.php">
      <i class="bi bi-newspaper me-2"></i><?php echo htmlspecialchars($L['site_title']); ?>
    </a>
    <button class="navbar-toggler navbar-dark" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav mx-auto gap-1">
        <li class="nav-item"><a class="nav-link active" href="?lang=<?php echo urlencode($lang); ?>"><?php echo $L['nav_home']; ?></a></li>
        <li class="nav-item"><a class="nav-link" href="?lang=<?php echo urlencode($lang); ?>"><?php echo $L['nav_articles']; ?></a></li>
        <li class="nav-item"><a class="nav-link" href="?lang=<?php echo urlencode($lang); ?>"><?php echo $L['nav_about']; ?></a></li>
      </ul>
      <!-- Language Switcher -->
      <div class="lang-switcher">
        <a class="lang-btn <?php echo (strpos($lang,'en') !== false) ? 'active' : ''; ?>" href="?lang=languages/en.php">🇬🇧 EN</a>
        <a class="lang-btn <?php echo (strpos($lang,'es') !== false) ? 'active' : ''; ?>" href="?lang=languages/es.php">🇪🇸 ES</a>
        <a class="lang-btn <?php echo (strpos($lang,'de') !== false) ? 'active' : ''; ?>" href="?lang=languages/de.php">🇩🇪 DE</a>
        <a class="lang-btn <?php echo (strpos($lang,'fr') !== false) ? 'active' : ''; ?>" href="?lang=languages/fr.php">🇫🇷 FR</a>
        <a class="lang-btn <?php echo (strpos($lang,'ar') !== false) ? 'active' : ''; ?>" href="?lang=languages/ar.php">🇸🇦 AR</a>
      </div>
    </div>
  </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container <?php echo $rtl ? 'text-end' : 'text-center'; ?>">
    <div class="hero-badge"><i class="bi bi-translate me-1"></i> Multi-Language CMS Platform</div>
    <h1 class="hero-title mx-auto" style="max-width:780px;"><?php echo htmlspecialchars($L['hero_title']); ?></h1>
    <p class="hero-subtitle mx-auto mt-3"><?php echo htmlspecialchars($L['hero_subtitle']); ?></p>
  </div>
</section>

<!-- Main Content -->
<main class="container my-5">
  <div class="row g-4">

    <!-- Articles Column -->
    <div class="col-lg-8">
      <div class="article-card mb-3">
        <span class="article-cat"><i class="bi bi-shield-lock me-1"></i> Security</span>
        <div class="article-title"><?php echo htmlspecialchars($L['post1_title']); ?></div>
        <p class="article-desc"><?php echo htmlspecialchars($L['post1_desc']); ?></p>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-3 border-top" style="border-color:rgba(255,255,255,.08)!important">
          <small class="text-muted font-monospace"><i class="bi bi-calendar3 me-1"></i>August 4, 2026 &nbsp;&bull;&nbsp; <i class="bi bi-clock me-1"></i>7 min read</small>
          <a href="#" class="btn-read" onclick="return false;"><?php echo htmlspecialchars($L['read_more']); ?> <i class="bi bi-arrow-<?php echo $rtl ? 'left' : 'right'; ?>"></i></a>
        </div>
      </div>

      <div class="article-card">
        <span class="article-cat"><i class="bi bi-cpu me-1"></i> Architecture</span>
        <div class="article-title"><?php echo htmlspecialchars($L['post2_title']); ?></div>
        <p class="article-desc"><?php echo htmlspecialchars($L['post2_desc']); ?></p>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-3 border-top" style="border-color:rgba(255,255,255,.08)!important">
          <small class="text-muted font-monospace"><i class="bi bi-calendar3 me-1"></i>July 29, 2026 &nbsp;&bull;&nbsp; <i class="bi bi-clock me-1"></i>10 min read</small>
          <a href="#" class="btn-read" onclick="return false;"><?php echo htmlspecialchars($L['read_more']); ?> <i class="bi bi-arrow-<?php echo $rtl ? 'left' : 'right'; ?>"></i></a>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
      <div class="sidebar-card mb-3">
        <div class="sidebar-heading"><i class="bi bi-globe2 me-1"></i> Available Languages</div>
        <div class="d-flex flex-wrap gap-1">
          <a class="tag-pill" href="?lang=languages/en.php">🇬🇧 English</a>
          <a class="tag-pill" href="?lang=languages/es.php">🇪🇸 Español</a>
          <a class="tag-pill" href="?lang=languages/de.php">🇩🇪 Deutsch</a>
          <a class="tag-pill" href="?lang=languages/fr.php">🇫🇷 Français</a>
          <a class="tag-pill" href="?lang=languages/ar.php">🇸🇦 العربية</a>
        </div>
      </div>

      <div class="sidebar-card mb-3">
        <div class="sidebar-heading"><i class="bi bi-tags me-1"></i> Popular Topics</div>
        <div class="d-flex flex-wrap gap-1">
          <span class="tag-pill">Security</span>
          <span class="tag-pill">PHP</span>
          <span class="tag-pill">LFI / Path Traversal</span>
          <span class="tag-pill">Microservices</span>
          <span class="tag-pill">i18n</span>
          <span class="tag-pill">Localization</span>
          <span class="tag-pill">CMS Architecture</span>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-heading"><i class="bi bi-rss me-1"></i> Newsletter</div>
        <p class="text-muted small mb-3">Get the latest security and architecture articles delivered weekly.</p>
        <form onsubmit="event.preventDefault(); alert('Subscribed!');">
          <input type="email" class="form-control bg-dark border-secondary text-white mb-2" placeholder="your@email.com" required>
          <button class="btn btn-sm btn-outline-info w-100 rounded-pill">Subscribe</button>
        </form>
      </div>
    </div>

  </div>
</main>

<!-- Footer -->
<footer class="text-center">
  <div class="container">
    <p class="mb-0"><?php echo htmlspecialchars($L['footer']); ?></p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
  crossorigin="anonymous"></script>
</body>
</html>
