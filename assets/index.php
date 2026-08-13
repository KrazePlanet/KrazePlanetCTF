<?php
$p = '';
if (isset($_GET['p'])) {
    $p = $_GET['p'];
}

$posts = [
    [
        'id'       => 'patch-28-1',
        'category' => 'Update',
        'title'    => 'PUBG Update #28.1 — Patch Notes',
        'date'     => 'Dec 4, 2019',
        'author'   => 'PUBG Community Team',
        'excerpt'  => 'This patch brings sweeping changes to Erangel, introduces dynamic weather events, and adjusts vehicle damage across all maps. Read the full notes below.',
        'tags'     => ['Erangel', 'Vehicles', 'Balance'],
        'img'      => 'https://wstatic-prod-boc.krafton.com/common/content/news/20251002/YBBOAv8w.jpg',
    ],
    [
        'id'       => 'winter-event',
        'category' => 'Event',
        'title'    => 'Winter Warfare Event — Limited Time Mode',
        'date'     => 'Dec 2, 2019',
        'author'   => 'PUBG Events Team',
        'excerpt'  => 'Drop into the frozen tundra of Vikendi for the Winter Warfare limited time mode. Exclusive cosmetics and weapon skins available for a limited period.',
        'tags'     => ['Vikendi', 'LTM', 'Event'],
        'img'      => 'https://wstatic-prod-boc.krafton.com/common/content/news/20260306/meBA6NLd.jpg',
    ],
    [
        'id'       => 'ranked-season-6',
        'category' => 'Competitive',
        'title'    => 'Ranked Season 6 — What\'s New',
        'date'     => 'Nov 28, 2019',
        'author'   => 'PUBG Esports',
        'excerpt'  => 'Season 6 of Ranked play is here. New tier rewards, updated matchmaking algorithm, and a revised point system aim to create fairer matches at every level.',
        'tags'     => ['Ranked', 'Season 6', 'Competitive'],
        'img'      => 'https://wstatic-prod-boc.krafton.com/common/content/news/20240702/MSivBcTY.jpg',
    ],
    [
        'id'       => 'anti-cheat',
        'category' => 'Security',
        'title'    => 'Anti-Cheat Improvements — November Report',
        'date'     => 'Nov 25, 2019',
        'author'   => 'PUBG Security Team',
        'excerpt'  => 'Over 1.2 million accounts banned in November. New machine learning detection models are now active on all live servers. We are committed to fair play.',
        'tags'     => ['Anti-Cheat', 'Security', 'Bans'],
        'img'      => 'https://wstatic-prod-boc.krafton.com/common/content/news/20241008/DtryFQfy.jpg',
    ],
    [
        'id'       => 'weapon-mastery',
        'category' => 'Feature',
        'title'    => 'Weapon Mastery System Expansion',
        'date'     => 'Nov 20, 2019',
        'author'   => 'PUBG Dev Team',
        'excerpt'  => 'Earn XP for every weapon you use and unlock exclusive cosmetic rewards. The expanded Weapon Mastery system now covers all 40+ weapons in the game.',
        'tags'     => ['Weapons', 'Mastery', 'Cosmetics'],
        'img'      => 'https://wstatic-prod-boc.krafton.com/common/content/news/20241104/rtkzIW6f.jpg',
    ],
    [
        'id'       => 'survivor-pass',
        'category' => 'Store',
        'title'    => 'Survivor Pass: Cold Front — Now Available',
        'date'     => 'Nov 15, 2019',
        'author'   => 'PUBG Store',
        'excerpt'  => 'The new Survivor Pass includes 90 levels of challenges and rewards, including 2 outfit sets, 3 weapon skins, and the exclusive Snowmobile cosmetic.',
        'tags'     => ['Pass', 'Cosmetics', 'Store'],
        'img'      => 'https://wstatic-prod-boc.krafton.com/common/content/news/20240308/xkUJ99w2.jpg',
    ],
];

$category_colors = [
    'Update'      => '#f5c518',
    'Event'       => '#58a4b0',
    'Competitive' => '#7bc67e',
    'Security'    => '#e05c5c',
    'Feature'     => '#f5a623',
    'Store'       => '#5588cc',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUBG — Community Feed</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --pubg-bg:      #0a0a0a;
            --pubg-surface: #111111;
            --pubg-card:    #181818;
            --pubg-border:  #2a2a2a;
            --pubg-yellow:  #f5c518;
            --pubg-orange:  #f5a623;
            --pubg-text:    #e8e8e8;
            --pubg-muted:   #888888;
        }

        body {
            background: var(--pubg-bg);
            color: var(--pubg-text);
            font-family: 'Segoe UI', Arial, sans-serif;
            min-height: 100vh;
        }
        .lab-badge-real {
            background: linear-gradient(90deg, #f5c518, #f5a623);
            color: #000;
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

        /* ── Header / Nav ── */
        .site-header {
            background: var(--pubg-surface);
            border-bottom: 1px solid var(--pubg-border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        .logo-box {
            background: var(--pubg-yellow);
            color: #000;
            font-weight: 900;
            font-size: 0.85rem;
            letter-spacing: 0.12em;
            padding: 0.3rem 0.6rem;
            border-radius: 2px;
        }
        .logo-text {
            color: var(--pubg-text);
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.05em;
        }
        .header-nav {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        .header-nav a {
            color: var(--pubg-muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            letter-spacing: 0.03em;
            transition: color 0.2s;
        }
        .header-nav a:hover, .header-nav a.active { color: var(--pubg-yellow); }
        .btn-play {
            background: var(--pubg-yellow);
            color: #000;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 2px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-play:hover { background: var(--pubg-orange); }

        /* ── Hero banner ── */
        .hero {
            background: linear-gradient(180deg, #111 0%, #0a0a0a 100%);
            border-bottom: 1px solid var(--pubg-border);
            padding: 3rem 2rem 2rem;
            text-align: center;
        }
        .hero-label {
            font-size: 0.7rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--pubg-yellow);
            margin-bottom: 0.75rem;
        }
        .hero h1 {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        .hero p { color: var(--pubg-muted); font-size: 0.9rem; }

        /* ── Filter bar ── */
        .filter-bar {
            background: var(--pubg-surface);
            border-bottom: 1px solid var(--pubg-border);
            padding: 1rem 2rem;
        }
        .filter-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .filter-label {
            font-size: 0.75rem;
            color: var(--pubg-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
            white-space: nowrap;
        }
        .filter-form {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            max-width: 420px;
        }
        .filter-form input[type="text"] {
            flex: 1;
            background: var(--pubg-card);
            border: 1px solid var(--pubg-border);
            color: var(--pubg-text);
            padding: 0.5rem 0.9rem;
            font-size: 0.85rem;
            font-family: inherit;
            border-radius: 2px;
            outline: none;
            transition: border-color 0.2s;
        }
        .filter-form input[type="text"]:focus { border-color: var(--pubg-yellow); }
        .filter-form button {
            background: var(--pubg-yellow);
            color: #000;
            border: none;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-radius: 2px;
            cursor: pointer;
            font-family: inherit;
        }
        .filter-form button:hover { background: var(--pubg-orange); }
        .filter-chips { display: flex; gap: 0.4rem; flex-wrap: wrap; }
        .chip {
            background: var(--pubg-card);
            border: 1px solid var(--pubg-border);
            color: var(--pubg-muted);
            font-size: 0.72rem;
            padding: 0.25rem 0.7rem;
            border-radius: 2px;
            text-decoration: none;
            transition: all 0.2s;
        }
        .chip:hover { border-color: var(--pubg-yellow); color: var(--pubg-yellow); }

        /* ── Feed results indicator ── */
        /*
         * VULNERABILITY NOTE (for lab):
         * The $p parameter is reflected UNESCAPED inside the data-query attribute below.
         * This allows an attacker to break out of the attribute context and inject HTML.
         * Payload: '><img src=a onerror=alert(document.cookie)>
         */

        /* ── Main layout ── */
        .main-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 2rem 4rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--pubg-border);
        }
        .section-header h2 {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: var(--pubg-muted);
        }
        .section-header span { font-size: 0.75rem; color: var(--pubg-muted); }

        /* ── Post grid ── */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }
        .post-card {
            background: var(--pubg-card);
            border: 1px solid var(--pubg-border);
            border-radius: 3px;
            overflow: hidden;
            transition: border-color 0.2s, transform 0.15s;
        }
        .post-card:hover { border-color: #444; transform: translateY(-2px); }
        .post-thumb {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
            background: #1a1a1a;
        }
        .post-body { padding: 1.25rem; }
        .post-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }
        .post-category {
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.2rem 0.5rem;
            border-radius: 2px;
        }
        .post-date { font-size: 0.72rem; color: var(--pubg-muted); }
        .post-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--pubg-text);
            margin-bottom: 0.6rem;
            line-height: 1.4;
            text-decoration: none;
            display: block;
        }
        .post-title:hover { color: var(--pubg-yellow); }
        .post-excerpt { font-size: 0.82rem; color: var(--pubg-muted); line-height: 1.6; margin-bottom: 1rem; }
        .post-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .post-author { font-size: 0.72rem; color: var(--pubg-muted); }
        .post-tags { display: flex; gap: 0.3rem; flex-wrap: wrap; }
        .tag {
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--pubg-border);
            color: #555;
            font-size: 0.62rem;
            padding: 0.1rem 0.4rem;
            border-radius: 2px;
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
            background: var(--pubg-surface);
            border-top: 1px solid var(--pubg-border);
            color: var(--pubg-muted);
            padding: 2rem;
            text-align: center;
            font-size: 0.78rem;
        }
    </style>
</head>
<body>

    <!-- Site header -->
    <header class="site-header">
        <div class="header-inner">
            <a href="#" class="logo">
                <span class="logo-box">PUBG</span>
                <span class="logo-text">BATTLEGROUNDS</span>
            </a>
            <nav class="header-nav">
                <a href="#">Play</a>
                <a href="#">News</a>
                <a href="#">Esports</a>
                <a href="#" class="active">Community</a>
                <a href="#">Store</a>
                <a href="#" class="btn-play">Play Now</a>
            </nav>
        </div>
    </header>

    <!-- Hero -->
    <div class="hero">
        <div class="hero-label">Community Hub</div>
        <h1>Latest News &amp; Updates</h1>
        <p>Patch notes, events, and announcements from the PUBG team</p>
    </div>

    <!-- Filter bar — VULNERABLE: $p is reflected raw inside data-query attribute -->
    <div class="filter-bar">
        <div class="filter-inner">
            <span class="filter-label">Filter:</span>
            <form class="filter-form" method="GET" action="">
                <input type="text" name="p" placeholder="Search posts..."
                    value="<?php echo htmlspecialchars($p, ENT_QUOTES); ?>">
                <button type="submit"><i class="bi bi-search"></i> Search</button>
            </form>
            <div class="filter-chips">
                <a href="?p=update" class="chip">Updates</a>
                <a href="?p=event" class="chip">Events</a>
                <a href="?p=ranked" class="chip">Ranked</a>
                <a href="?p=patch" class="chip">Patch Notes</a>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="main-wrap">

        <!--
            VULNERABLE LINE BELOW:
            The $p parameter is echoed directly into the data-query attribute WITHOUT htmlspecialchars.
            Payload to exploit: '><img src=a onerror=alert(document.cookie)>
        -->
        <div class="section-header"
             data-query='<?php echo $p; ?>'
             data-page='feed'>
            <h2>
                <?php if ($p !== ''): ?>
                    Showing results for &ldquo;<?php echo htmlspecialchars($p, ENT_QUOTES); ?>&rdquo;
                <?php else: ?>
                    All Posts
                <?php endif; ?>
            </h2>
            <span><?php echo count($posts); ?> articles</span>
        </div>

        <div class="posts-grid">
            <?php foreach ($posts as $post):
                $color = $category_colors[$post['category']] ?? '#888';
            ?>
            <article class="post-card">
                <img class="post-thumb" src="<?php echo $post['img']; ?>" alt="">
                <div class="post-body">
                    <div class="post-meta">
                        <span class="post-category"
                              style="background:<?php echo $color; ?>22;color:<?php echo $color; ?>;border:1px solid <?php echo $color; ?>44;">
                            <?php echo htmlspecialchars($post['category']); ?>
                        </span>
                        <span class="post-date"><?php echo htmlspecialchars($post['date']); ?></span>
                    </div>
                    <a href="#" class="post-title"><?php echo htmlspecialchars($post['title']); ?></a>
                    <p class="post-excerpt"><?php echo htmlspecialchars($post['excerpt']); ?></p>
                    <div class="post-footer">
                        <span class="post-author">
                            <i class="bi bi-person-fill" style="font-size:0.7rem;margin-right:0.2rem;"></i>
                            <?php echo htmlspecialchars($post['author']); ?>
                        </span>
                        <div class="post-tags">
                            <?php foreach ($post['tags'] as $tag): ?>
                            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

    </div>

    <footer>
        <p>&copy; 2019 PUBG Corporation. All rights reserved.</p>
        <p style="margin-top:0.4rem; opacity:0.4; font-size:0.7rem;">This is a simulated lab environment for security training purposes only.</p>
    </footer>

</body>
</html>
