<?php
$search = "";
if (isset($_GET["search"])) {
    $search = $_GET["search"];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reflected XSS in Search Function</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;1,300&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #0b0d13;
            --surface:   #13161f;
            --border:    rgba(255,255,255,0.07);
            --accent:    #e8ff47;
            --accent2:   #47c5ff;
            --text:      #f0f2f8;
            --muted:     #6b7280;
            --glow:      rgba(232, 255, 71, 0.18);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ── Noise texture overlay ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            pointer-events: none;
            z-index: 0;
            opacity: 0.6;
        }

        /* ── Ambient blobs ── */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(120px);
            pointer-events: none;
            z-index: 0;
        }
        .blob-1 {
            width: 500px; height: 500px;
            background: rgba(232,255,71,0.06);
            top: -180px; left: -120px;
        }
        .blob-2 {
            width: 400px; height: 400px;
            background: rgba(71,197,255,0.07);
            bottom: -100px; right: -80px;
        }

        /* ── Header ── */
        header {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 28px 48px;
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(12px);
        }

        .wordmark {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: -0.02em;
            color: var(--text);
            text-decoration: none;
        }
        .wordmark span { color: var(--accent); }

        .nav-pill {
            display: flex;
            gap: 6px;
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 5px;
            border-radius: 50px;
        }
        .nav-pill a {
            font-size: 0.8rem;
            font-weight: 400;
            color: var(--muted);
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 50px;
            transition: all 0.2s;
        }
        .nav-pill a:hover, .nav-pill a.active {
            background: rgba(255,255,255,0.06);
            color: var(--text);
        }

        /* ── Main layout ── */
        main {
            position: relative;
            z-index: 10;
            max-width: 760px;
            margin: 0 auto;
            padding: 80px 24px 120px;
        }

        /* ── Hero text ── */
        .hero {
            margin-bottom: 48px;
            animation: fadeUp 0.6s ease both;
        }
        .hero-eyebrow {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--accent);
            margin-bottom: 14px;
        }
        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: clamp(2.4rem, 5vw, 3.8rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
            color: var(--text);
        }
        .hero h1 em {
            font-style: normal;
            color: transparent;
            -webkit-text-stroke: 1.5px var(--accent);
        }
        .hero p {
            margin-top: 16px;
            font-size: 1rem;
            color: var(--muted);
            font-weight: 300;
            max-width: 480px;
            line-height: 1.65;
        }

        /* ── Search bar ── */
        .search-wrap {
            position: relative;
            animation: fadeUp 0.6s 0.1s ease both;
        }
        .search-icon {
            position: absolute;
            left: 22px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--muted);
            pointer-events: none;
            transition: color 0.2s;
        }
        .search-wrap:focus-within .search-icon { color: var(--accent); }

        input[name="search"] {
            width: 100%;
            padding: 18px 60px 18px 54px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 1rem;
            font-weight: 300;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
        }
        input[name="search"]::placeholder { color: var(--muted); }
        input[name="search"]:focus {
            border-color: rgba(232,255,71,0.4);
            box-shadow: 0 0 0 4px var(--glow), 0 8px 32px rgba(0,0,0,0.4);
        }

        .search-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--accent);
            border: none;
            border-radius: 10px;
            padding: 9px 18px;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.03em;
            color: #0b0d13;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
        }
        .search-btn:hover {
            transform: translateY(-50%) scale(1.04);
            box-shadow: 0 4px 20px rgba(232,255,71,0.35);
        }

        /* ── Quick tags ── */
        .tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 16px;
            animation: fadeUp 0.6s 0.18s ease both;
        }
        .tags span {
            font-size: 0.72rem;
            color: var(--muted);
            padding-top: 2px;
        }
        .tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            border: 1px solid var(--border);
            font-size: 0.75rem;
            color: var(--muted);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.18s;
            background: transparent;
        }
        .tag:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: rgba(232,255,71,0.05);
        }

        /* ── Divider ── */
        .divider {
            height: 1px;
            background: var(--border);
            margin: 48px 0 32px;
        }

        /* ── Results area ── */
        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            animation: fadeUp 0.5s ease both;
        }
        .results-header h2 {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: -0.02em;
        }
        .results-header h2 mark {
            background: none;
            color: var(--accent);
        }
        .results-count {
            font-size: 0.78rem;
            color: var(--muted);
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 4px 12px;
            border-radius: 50px;
        }

        /* ── Empty state ── */
        .empty-state {
            text-align: center;
            padding: 64px 24px;
            animation: fadeUp 0.5s 0.1s ease both;
        }
        .empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 20px;
            border-radius: 18px;
            background: var(--surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }
        .empty-state h3 {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 8px;
        }
        .empty-state p {
            font-size: 0.9rem;
            color: var(--muted);
            font-weight: 300;
            line-height: 1.6;
        }

        /* ── Footer ── */
        footer {
            position: relative;
            z-index: 10;
            border-top: 1px solid var(--border);
            padding: 22px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        footer p {
            font-size: 0.75rem;
            color: var(--muted);
        }
        footer a { color: var(--accent); text-decoration: none; }

        /* ── Animations ── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── Mobile ── */
        @media (max-width: 600px) {
            header { padding: 20px 20px; }
            .nav-pill { display: none; }
            main { padding: 48px 16px 80px; }
            footer { flex-direction: column; gap: 8px; padding: 20px; text-align: center; }
        }
    </style>
</head>
<body>

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<header>
    <a class="wordmark" href="/">Kraze<span>Planet</span>Labs</a>
    <nav class="nav-pill">
        <a href="#" class="active">Search</a>
        <a href="#">Explore</a>
        <a href="#">Docs</a>
        <a href="#">About</a>
    </nav>
</header>

<main>
    <?php if (!$search): ?>
    <div class="hero">
        <p class="hero-eyebrow">Knowledge Base &amp; Search</p>
        <h1>Find what you're<br><em>looking</em> for.</h1>
        <p>Search across all of VexiumCTF — documentation, articles, experiments, and more.</p>
    </div>
    <?php endif; ?>

    <div class="search-wrap">
        <form method="GET" autocomplete="off">
            <svg class="search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" name="search" placeholder="Search anything…">
            <button type="submit" class="search-btn">Search</button>
        </form>
    </div>

    <?php if (!$search): ?>
    <div class="tags">
        <span>Try:</span>
        <a class="tag" href="?q=getting+started">getting started</a>
        <a class="tag" href="?q=API+docs">API docs</a>
        <a class="tag" href="?q=tutorials">tutorials</a>
        <a class="tag" href="?q=changelog">changelog</a>
    </div>
    <?php endif; ?>

    <?php if ($search): ?>
    <div class="divider"></div>
    <div class="results-header">
        <h2>Results for <mark>"<?php echo $search; ?>"</mark></h2>
        <span class="results-count">0 results</span>
    </div>
    <div class="empty-state">
        <div class="empty-icon">🔭</div>
        <h3>Nothing found yet</h3>
        <p>No results matched <strong style="color:var(--text)">"<?php echo $search; ?>"</strong>.<br>
        Try different keywords, or check back as we grow our content.</p>
    </div>
    <?php endif; ?>
</main>

<footer>
    <p>© <?php echo date('Y'); ?> VexiumCTF</p>
    <p>Built with <a href="#">care &amp; curiosity</a></p>
</footer>

</body>
</html>