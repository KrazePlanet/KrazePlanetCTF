<?php
$search = '';
$results = [];
$result_count = 0;

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $search = $_GET['search'];
    $result_count = 5;
    $results = [
        ['title' => 'How to freeze your credit report', 'url' => '/personal/credit-report-services/credit-freeze/', 'excerpt' => 'Learn how to place a security freeze on your Equifax credit report to help protect against unauthorized access.'],
        ['title' => 'Understanding your credit score', 'url' => '/personal/education/credit/score/', 'excerpt' => 'Your credit score is a three-digit number that lenders use to evaluate your creditworthiness. Here\'s how it works.'],
        ['title' => 'Disputing information on your credit report', 'url' => '/personal/credit-report-services/dispute-credit/', 'excerpt' => 'If you believe there is inaccurate information on your credit report, you have the right to dispute it.'],
        ['title' => 'Equifax Data Breach Settlement — FAQs', 'url' => '/personal/education/credit/report/data-breach-settlement/', 'excerpt' => 'Find answers to frequently asked questions about the Equifax data breach settlement and your eligibility.'],
        ['title' => 'How to get a free copy of your credit report', 'url' => '/personal/credit-report-services/free-credit-reports/', 'excerpt' => 'You are entitled to a free credit report from each of the three major credit bureaus every 12 months.'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - Search Results</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --eq-navy: #003057;
            --eq-blue: #0062a3;
            --eq-light-blue: #e8f4fd;
            --eq-red: #e31837;
            --eq-gray: #f5f5f5;
            --eq-border: #d8d8d8;
            --eq-text: #333333;
            --eq-muted: #666666;
        }

        body {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            background: #ffffff;
            color: var(--eq-text);
            min-height: 100vh;
        }
        .lab-badge-real {
            background: linear-gradient(90deg, #e31837, #f97316);
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

        /* ── Header ── */
        header {
            background: var(--eq-navy);
            padding: 0;
        }
        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--eq-red);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1.2rem;
            color: #fff;
        }
        .logo-text {
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.02em;
        }
        .header-nav {
            display: flex;
            gap: 1.5rem;
        }
        .header-nav a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 0.3rem 0;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .header-nav a:hover { color: #fff; border-bottom-color: var(--eq-red); }

        .header-bottom {
            background: var(--eq-blue);
            padding: 0.6rem 2rem;
        }
        .breadcrumb {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
        }
        .breadcrumb a { color: rgba(255,255,255,0.85); text-decoration: none; }
        .breadcrumb a:hover { color: #fff; }
        .breadcrumb span { margin: 0 0.4rem; }

        /* ── Hero search ── */
        .search-hero {
            background: linear-gradient(135deg, var(--eq-navy) 0%, var(--eq-blue) 100%);
            padding: 3rem 2rem 2.5rem;
            text-align: center;
        }
        .search-hero h1 {
            color: #fff;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .search-hero p { color: rgba(255,255,255,0.75); font-size: 0.95rem; margin-bottom: 1.5rem; }
        .search-form {
            display: flex;
            max-width: 640px;
            margin: 0 auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            border-radius: 8px;
            overflow: hidden;
        }
        .search-form input[type="text"] {
            flex: 1;
            padding: 0.9rem 1.2rem;
            font-size: 1rem;
            border: none;
            outline: none;
            font-family: inherit;
            color: var(--eq-text);
        }
        .search-form button {
            background: var(--eq-red);
            color: #fff;
            border: none;
            padding: 0.9rem 1.6rem;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: background 0.2s;
        }
        .search-form button:hover { background: #c5122f; }

        /* ── Main layout ── */
        .main-wrap {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }

        /* ── Results header ── */
        .results-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--eq-border);
            margin-bottom: 1.5rem;
        }
        .results-header h2 { font-size: 1.1rem; font-weight: 600; color: var(--eq-navy); }
        .results-count { font-size: 0.85rem; color: var(--eq-muted); }

        /* ── Result item ── */
        .result-item {
            padding: 1.25rem 0;
            border-bottom: 1px solid var(--eq-border);
        }
        .result-item:last-child { border-bottom: none; }
        .result-item a.result-title {
            color: var(--eq-blue);
            font-size: 1.05rem;
            font-weight: 600;
            text-decoration: none;
            display: block;
            margin-bottom: 0.3rem;
        }
        .result-item a.result-title:hover { text-decoration: underline; color: var(--eq-navy); }
        .result-url { font-size: 0.78rem; color: #2e7d32; margin-bottom: 0.4rem; }
        .result-excerpt { font-size: 0.9rem; color: var(--eq-muted); line-height: 1.6; }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-icon { font-size: 3rem; color: var(--eq-border); margin-bottom: 1rem; }
        .empty-state h2 { font-size: 1.2rem; color: var(--eq-navy); margin-bottom: 0.5rem; }
        .empty-state p { color: var(--eq-muted); font-size: 0.9rem; }

        /* ── Popular topics ── */
        .popular-section { margin-top: 2.5rem; }
        .popular-section h3 { font-size: 0.85rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--eq-muted); margin-bottom: 1rem; }
        .topics-grid { display: flex; flex-wrap: wrap; gap: 0.5rem; }
        .topic-chip {
            background: var(--eq-light-blue);
            color: var(--eq-blue);
            border: 1px solid #bee3f8;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        .topic-chip:hover { background: var(--eq-blue); color: #fff; }

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
            background: var(--eq-navy);
            color: rgba(255,255,255,0.6);
            padding: 2rem;
            text-align: center;
            font-size: 0.8rem;
        }
        footer a { color: rgba(255,255,255,0.6); }
    </style>
</head>
<body>

    <!-- Site header (simulated Equifax) -->
    <header>
        <div class="header-top">
            <a href="#" class="logo">
                <div class="logo-icon">E</div>
                <span class="logo-text">Equifax</span>
            </a>
            <nav class="header-nav">
                <a href="#">Products</a>
                <a href="#">Personal</a>
                <a href="#">Business</a>
                <a href="#">Help Center</a>
                <a href="#">Sign In</a>
            </nav>
        </div>
        <div class="header-bottom">
            <div class="breadcrumb">
                <a href="#">Home</a><span>›</span>
                <a href="#">Help Center</a><span>›</span>
                Search Results
            </div>
        </div>
    </header>

    <!-- Search hero -->
    <div class="search-hero">
        <h1>How can we help you?</h1>
        <p>Search our Help Center for answers to common questions</p>
        <form class="search-form" method="GET" action="">
            <input type="text" name="search" placeholder="Search help articles..."
                value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
            <button type="submit">
                <i class="bi bi-search"></i> Search
            </button>
        </form>
    </div>

    <!-- Results -->
    <div class="main-wrap">
        <?php if ($search !== ''): ?>
            <div class="results-header">
                <h2>Search Results for "<?php echo htmlspecialchars($search, ENT_QUOTES); ?>"</h2>
                <span class="results-count"><?php echo $result_count; ?> result<?php echo $result_count !== 1 ? 's' : ''; ?> found</span>
            </div>
            <div class="results-list">
                <?php foreach ($results as $r): ?>
                <div class="result-item">
                    <a href="#" class="result-title"><?php echo htmlspecialchars($r['title']); ?></a>
                    <div class="result-url">equifax.com<?php echo htmlspecialchars($r['url']); ?></div>
                    <div class="result-excerpt"><?php echo htmlspecialchars($r['excerpt']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-search"></i></div>
                <h2>Enter a search term above</h2>
                <p>Try searching for "credit freeze", "dispute", or "credit score"</p>
            </div>
        <?php endif; ?>

        <!-- Popular topics -->
        <div class="popular-section">
            <h3>Popular Topics</h3>
            <div class="topics-grid">
                <a href="?search=credit+freeze" class="topic-chip">Credit Freeze</a>
                <a href="?search=dispute" class="topic-chip">Dispute Information</a>
                <a href="?search=credit+score" class="topic-chip">Credit Score</a>
                <a href="?search=free+report" class="topic-chip">Free Credit Report</a>
                <a href="?search=identity+theft" class="topic-chip">Identity Theft</a>
                <a href="?search=data+breach" class="topic-chip">Data Breach</a>
                <a href="?search=lock+unlock" class="topic-chip">Lock &amp; Unlock</a>
            </div>
        </div>

    <footer>
        <p>&copy; 2023 Equifax Inc. &mdash; <a href="#">Privacy Policy</a> &mdash; <a href="#">Terms of Use</a></p>
        <p style="margin-top:0.4rem; font-size:0.7rem; opacity:0.5;">This is a simulated lab environment for security training purposes only.</p>
    </footer>

    <!-- Vulnerable analytics tracking script -->
    <!-- The search term is reflected here WITHOUT JavaScript string escaping -->
    <script type="text/javascript">
        var Analytics = {
            trackEvent: function(eventName, params) {
                // Analytics stub — in a real site this would send data to a tracking endpoint
                console.log('[Analytics]', eventName, params);
            }
        };

        window.onload = function(e) {
            Analytics.trackEvent('<?php echo ($search !== '' ? 'searchReturned' : 'emptySearch'); ?>', {
                internalSearchTerm: "<?php echo $search; ?>",
                numOfSearchResultsReturned: <?php echo $result_count; ?>
            });
        }
    </script>

</body>
</html>
