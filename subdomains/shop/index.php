<?php
// ── Parameter handling (intentionally unsanitized — XSS lab) ────────
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$sort      = isset($_GET['sort'])     ? $_GET['sort']     : 'newest';

// ── Fake lab data ───────────────────────────────────────────────────
$labs = [
    ['id'=>1, 'name'=>'Quantum Foam Simulator',   'category'=>'physics',   'date'=>'2025-03-12', 'views'=>4821, 'tag'=>'New',     'desc'=>'Visualise sub-Planck-scale fluctuations in real time using Monte Carlo lattice sampling.'],
    ['id'=>2, 'name'=>'DNA Origami Folder',        'category'=>'biology',   'date'=>'2025-02-28', 'views'=>3102, 'tag'=>'Hot',     'desc'=>'Design, fold, and stress-test custom DNA nanostructures in an interactive 3-D canvas.'],
    ['id'=>3, 'name'=>'Plasma Torch CFD',          'category'=>'physics',   'date'=>'2025-01-15', 'views'=>2748, 'tag'=>null,      'desc'=>'Computational fluid dynamics model of a high-frequency plasma torch at 10,000 K.'],
    ['id'=>4, 'name'=>'Enzyme Kinetics Bench',     'category'=>'chemistry', 'date'=>'2024-12-09', 'views'=>1990, 'tag'=>null,      'desc'=>'Michaelis-Menten and Hill equation explorer with live curve fitting against your own data.'],
    ['id'=>5, 'name'=>'Neural Spike Sorter',       'category'=>'computing', 'date'=>'2025-03-20', 'views'=>5540, 'tag'=>'New',     'desc'=>'Unsupervised spike sorting for multi-electrode arrays with PCA projection and cluster tuning.'],
    ['id'=>6, 'name'=>'Exoplanet Transit Lab',     'category'=>'space',     'date'=>'2025-03-01', 'views'=>6310, 'tag'=>'Popular', 'desc'=>'Simulate and analyse photometric transit curves for user-defined planetary systems.'],
    ['id'=>7, 'name'=>'CRISPR Off-Target Map',     'category'=>'biology',   'date'=>'2025-02-14', 'views'=>2201, 'tag'=>null,      'desc'=>'Predict and visualise off-target cleavage sites for any guide RNA across the human genome.'],
    ['id'=>8, 'name'=>'Reaction Diffusion Canvas', 'category'=>'chemistry', 'date'=>'2024-11-30', 'views'=>3870, 'tag'=>'Hot',     'desc'=>'Gray-Scott reaction-diffusion system in a GPU-accelerated browser canvas — edit feed/kill rates live.'],
    ['id'=>9, 'name'=>'Orbital Resonance Sandbox', 'category'=>'space',     'date'=>'2024-10-22', 'views'=>1450, 'tag'=>null,      'desc'=>'Place bodies in a solar system and watch resonance chains emerge from gravitational perturbations.'],
    ['id'=>10,'name'=>'Cellular Automata Studio',  'category'=>'computing', 'date'=>'2024-09-18', 'views'=>2980, 'tag'=>null,      'desc'=>'Build and run any 2-D totalistic rule on an infinite grid with pattern import/export.'],
];

// ── Filter ──────────────────────────────────────────────────────────
if ($category !== 'all') {
    $labs = array_filter($labs, fn($l) => $l['category'] === $category);
}

// ── Sort ────────────────────────────────────────────────────────────
usort($labs, function($a, $b) use ($sort) {
    return match($sort) {
        'oldest'  => strcmp($a['date'], $b['date']),
        'popular' => $b['views'] - $a['views'],
        'name'    => strcmp($a['name'], $b['name']),
        default   => strcmp($b['date'], $a['date']),
    };
});

// Raw values reflected directly into HTML — intentional for XSS lab
$raw_category = $category;
$raw_sort      = $sort;

// ── Helpers ─────────────────────────────────────────────────────────
function url($category, $sort) {
    return '?category=' . urlencode($category) . '&sort=' . urlencode($sort);
}
function fmt_views($n) {
    return $n >= 1000 ? round($n/1000, 1).'k' : $n;
}
function fmt_date($d) {
    return date('M j, Y', strtotime($d));
}
$category_labels = [
    'all'=>'All Labs','physics'=>'Physics','chemistry'=>'Chemistry',
    'biology'=>'Biology','computing'=>'Computing','space'=>'Space'
];
$sort_labels = ['newest'=>'Newest','oldest'=>'Oldest','popular'=>'Most Popular','name'=>'A → Z'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reflected XSS in Category Filter</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Epilogue:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #f5f0e8;
            --paper:    #faf7f2;
            --ink:      #1a1814;
            --ink-soft: #5c5750;
            --rule:     #ddd8ce;
            --accent:   #c0392b;
            --accent-s: rgba(192,57,43,0.08);
            --mono:     'Courier New', monospace;
        }

        body {
            font-family: 'Epilogue', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }

        /* ── Grain overlay ── */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 512 512' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23g)' opacity='0.035'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 999;
        }

        /* ── Header ── */
        header {
            border-bottom: 2px solid var(--ink);
            padding: 0 48px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: stretch;
            gap: 0;
        }
        .header-brand {
            padding: 22px 0;
            border-right: 1px solid var(--ink);
            padding-right: 32px;
            margin-right: 32px;
        }
        .wordmark {
            font-family: 'Instrument Serif', serif;
            font-size: 1.3rem;
            color: var(--ink);
            text-decoration: none;
            display: block;
        }
        .wordmark small {
            display: block;
            font-family: 'Epilogue', sans-serif;
            font-size: 0.65rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent);
            margin-top: 2px;
        }
        .header-nav {
            display: flex;
            align-items: center;
            gap: 28px;
        }
        .header-nav a {
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--ink-soft);
            text-decoration: none;
            transition: color 0.15s;
        }
        .header-nav a:hover, .header-nav a.active { color: var(--accent); }
        .header-issue {
            padding: 22px 0;
            border-left: 1px solid var(--ink);
            padding-left: 32px;
            margin-left: 32px;
            font-family: var(--mono);
            font-size: 0.7rem;
            color: var(--ink-soft);
            line-height: 1.7;
        }

        /* ── Page hero ── */
        .page-hero {
            border-bottom: 1px solid var(--rule);
            padding: 60px 48px 48px;
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: end;
            gap: 32px;
            animation: fadeIn 0.5s ease both;
        }
        .page-hero h1 {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(2.8rem, 6vw, 5rem);
            line-height: 1;
            letter-spacing: -0.02em;
        }
        .page-hero h1 em {
            font-style: italic;
            color: var(--accent);
        }
        .page-hero p {
            margin-top: 14px;
            font-size: 0.95rem;
            color: var(--ink-soft);
            font-weight: 300;
            max-width: 480px;
            line-height: 1.7;
        }
        .lab-count {
            font-family: var(--mono);
            font-size: 4rem;
            font-weight: 700;
            color: var(--rule);
            line-height: 1;
            letter-spacing: -0.04em;
            text-align: right;
        }
        .lab-count span {
            display: block;
            font-family: 'Epilogue', sans-serif;
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink-soft);
            text-align: right;
        }

        /* ── Toolbar ── */
        .toolbar {
            padding: 0 48px;
            border-bottom: 1px solid var(--rule);
            display: flex;
            align-items: stretch;
            gap: 0;
            animation: fadeIn 0.5s 0.05s ease both;
        }

        /* Category tabs */
        .cat-tabs {
            display: flex;
            align-items: center;
            gap: 2px;
            padding: 14px 0;
            flex: 1;
        }
        .cat-tab {
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 7px 14px;
            border-radius: 4px;
            text-decoration: none;
            color: var(--ink-soft);
            transition: all 0.15s;
            border: 1px solid transparent;
        }
        .cat-tab:hover { background: var(--accent-s); color: var(--accent); }
        .cat-tab.active {
            background: var(--ink);
            color: var(--bg);
            border-color: var(--ink);
        }

        /* Sort control */
        .sort-control {
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 1px solid var(--rule);
            padding: 14px 0 14px 28px;
            margin-left: 20px;
        }
        .sort-label {
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ink-soft);
            white-space: nowrap;
        }
        .sort-btns { display: flex; gap: 4px; }
        .sort-btn {
            font-family: 'Epilogue', sans-serif;
            font-size: 0.75rem;
            font-weight: 500;
            padding: 6px 11px;
            border-radius: 4px;
            border: 1px solid var(--rule);
            background: transparent;
            color: var(--ink-soft);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
            display: inline-block;
        }
        .sort-btn:hover { border-color: var(--ink-soft); color: var(--ink); }
        .sort-btn.active {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-s);
        }

        /* ── Lab grid ── */
        .lab-grid {
            padding: 40px 48px 80px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1px;
            background: var(--rule);
            border-bottom: 1px solid var(--rule);
        }

        .lab-card {
            background: var(--paper);
            padding: 32px 28px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            text-decoration: none;
            color: inherit;
            transition: background 0.18s;
            position: relative;
            animation: fadeIn 0.4s ease both;
        }
        .lab-card:hover { background: #fff; }
        .lab-card:hover .card-arrow { transform: translate(3px, -3px); }

        .card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }
        .card-category {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--accent);
            padding: 3px 9px;
            border: 1px solid var(--accent);
            border-radius: 3px;
        }
        .card-badge {
            font-family: var(--mono);
            font-size: 0.65rem;
            color: var(--ink-soft);
            border: 1px solid var(--rule);
            padding: 3px 8px;
            border-radius: 3px;
        }
        .card-badge.badge-new     { border-color: #27ae60; color: #27ae60; }
        .card-badge.badge-hot     { border-color: #e67e22; color: #e67e22; }
        .card-badge.badge-popular { border-color: var(--accent); color: var(--accent); }

        .card-name {
            font-family: 'Instrument Serif', serif;
            font-size: 1.4rem;
            line-height: 1.2;
            letter-spacing: -0.01em;
        }
        .card-desc {
            font-size: 0.85rem;
            color: var(--ink-soft);
            font-weight: 300;
            line-height: 1.65;
            flex: 1;
        }
        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid var(--rule);
        }
        .card-meta {
            font-family: var(--mono);
            font-size: 0.68rem;
            color: var(--ink-soft);
            display: flex;
            gap: 14px;
        }
        .card-arrow {
            width: 20px;
            height: 20px;
            color: var(--accent);
            transition: transform 0.2s;
            flex-shrink: 0;
        }

        /* ── Empty state ── */
        .empty {
            grid-column: 1 / -1;
            text-align: center;
            padding: 80px 24px;
            background: var(--paper);
        }
        .empty h3 {
            font-family: 'Instrument Serif', serif;
            font-size: 1.6rem;
            margin-bottom: 10px;
        }
        .empty p { font-size: 0.9rem; color: var(--ink-soft); font-weight: 300; }

        /* ── Footer ── */
        footer {
            padding: 22px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        footer p { font-size: 0.75rem; color: var(--ink-soft); }
        footer a { color: var(--accent); text-decoration: none; }

        /* ── Animations ── */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            header { padding: 0 20px; grid-template-columns: 1fr; }
            .header-issue, .header-nav { display: none; }
            .header-brand { border-right: none; padding: 18px 0; }
            .page-hero { padding: 40px 20px 32px; grid-template-columns: 1fr; }
            .lab-count { display: none; }
            .toolbar { padding: 0 20px; flex-direction: column; align-items: flex-start; }
            .sort-control { border-left: none; border-top: 1px solid var(--rule); padding: 14px 0; margin: 0; }
            .lab-grid { padding: 1px 0; }
            footer { padding: 20px; flex-direction: column; gap: 6px; text-align: center; }
        }
    </style>
</head>
<body>

<header>
    <div class="header-brand">
        <a class="wordmark" href="index.php">KrazePlanet<small>Research Division</small></a>
    </div>
    <nav class="header-nav">
        <a href="index.php">Search</a>
        <a href="labs.php" class="active">Labs</a>
        <a href="#">Docs</a>
        <a href="#">About</a>
    </nav>
    <div class="header-issue">
        <?php echo date('D, M j Y'); ?><br>
        Vol. IV - Issue 031
    </div>
</header>

<section class="page-hero">
    <div>
        <h1>Active<br><em>Experiments</em></h1>
        <p>Browse our open-ACCESS research labs. Each one is a live, interactive environment you can run in your browser — no installs required.</p>
    </div>
    <div class="lab-count">
        <?php echo str_pad(count($labs), 2, '0', STR_PAD_LEFT); ?>
        <span>Labs <?php echo $raw_category !== 'all' ? "in $raw_category" : 'available'; ?></span>
    </div>
</section>

<div class="toolbar">
    <div class="cat-tabs">
        <?php foreach ($category_labels as $slug => $label): ?>
            <a class="cat-tab <?php echo $raw_category === $slug ? 'active' : ''; ?>"
               href="<?php echo url($slug, $raw_sort); ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
        <?php if (!array_key_exists($raw_category, $category_labels) && $raw_category !== 'all'): ?>
            <!-- Unknown category reflected as active tab label -->
            <a class="cat-tab active" href="#"><?php echo $raw_category; ?></a>
        <?php endif; ?>
    </div>
    <div class="sort-control">
        <span class="sort-label">Sort by: <?php echo $raw_sort; ?></span>
        <div class="sort-btns">
            <?php foreach ($sort_labels as $slug => $label): ?>
                <a class="sort-btn <?php echo $raw_sort === $slug ? 'active' : ''; ?>"
                   href="<?php echo url($raw_category, $slug); ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="lab-grid">
    <?php if (empty($labs)): ?>
        <div class="empty">
            <h3>No labs found</h3>
            <p>No results for category "<strong><?php echo $raw_category; ?></strong>" — <a href="<?php echo url('all', $raw_sort); ?>">browse all categories</a>.</p>
        </div>
    <?php else: ?>
        <?php foreach (array_values($labs) as $i => $lab): ?>
            <a class="lab-card" href="#lab-<?php echo $lab['id']; ?>"
               style="animation-delay: <?php echo $i * 0.04; ?>s">
                <div class="card-top">
                    <span class="card-category"><?php echo ucfirst($lab['category']); ?></span>
                    <?php if ($lab['tag']): ?>
                        <span class="card-badge badge-<?php echo strtolower($lab['tag']); ?>">
                            <?php echo $lab['tag']; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-name"><?php echo htmlspecialchars($lab['name']); ?></div>
                <div class="card-desc"><?php echo htmlspecialchars($lab['desc']); ?></div>
                <div class="card-footer">
                    <div class="card-meta">
                        <span><?php echo fmt_date($lab['date']); ?></span>
                        <span><?php echo fmt_views($lab['views']); ?> views</span>
                    </div>
                    <svg class="card-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M7 17L17 7M7 7h10v10"/>
                    </svg>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<footer>
    <p>© <?php echo date('Y'); ?> KrazePlanet - <a href="#">Open Science Initiative</a></p>
    <p>Viewing: <strong><?php echo $raw_category; ?></strong> &bull; Sorted by <strong><?php echo $raw_sort; ?></strong></p>
</footer>

</body>
</html>