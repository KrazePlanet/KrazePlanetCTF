<?php
$post_id  = 't3_u9po1l';
$subreddit = 'askreddit';

// Primary: extract from URL path — 59.php/svc/shreddit/api/comments/{subreddit}/{post_id}/...
$path_info = $_SERVER['PATH_INFO'] ?? '';
if (preg_match('#^/svc/shreddit/api/comments/([^/]+)/([^/]+)#', $path_info, $m)) {
    $subreddit = $m[1];
    $post_id   = $m[2];
} elseif (isset($_GET['post_id'])) {
    $post_id   = $_GET['post_id'];
    $subreddit = $_GET['subreddit'] ?? 'askreddit';
}

$safe_post_id   = htmlspecialchars($post_id, ENT_QUOTES);
$safe_subreddit = htmlspecialchars($subreddit, ENT_QUOTES);

$post = [
    'title'   => 'What\'s something that\'s technically legal but feels morally wrong?',
    'author'  => 'throwaway_aq91',
    'score'   => '47.3k',
    'age'     => '14 hours ago',
    'flair'   => 'AskReddit',
    'awards'  => 3,
];

$comments = [
    [
        'id'      => 'i5sxroa',
        'author'  => 'VelvetThunder_x',
        'score'   => '34.2k',
        'age'     => '13h',
        'color'   => '#ff4500',
        'text'    => 'Replying to a "read" message hours later and pretending you just saw it. We all know. We all do it. Nobody talks about it.',
        'replies' => 847,
    ],
    [
        'id'      => 'i5t2mnb',
        'author'  => 'QuantumFogg',
        'score'   => '28.7k',
        'age'     => '13h',
        'color'   => '#7193ff',
        'text'    => 'Using the self-checkout lane with a full cart because there\'s no sign saying you can\'t.',
        'replies' => 512,
    ],
    [
        'id'      => 'i5u8kpl',
        'author'  => 'NeonSerpent42',
        'score'   => '19.1k',
        'age'     => '12h',
        'color'   => '#46d160',
        'text'    => 'Loud phone calls in public places. Not illegal, but the social contract clearly says this is wrong.',
        'replies' => 388,
    ],
    [
        'id'      => 'i5v3qrw',
        'author'  => 'MirrorBreaker',
        'score'   => '14.8k',
        'age'     => '11h',
        'color'   => '#ff585b',
        'text'    => 'Subscribing someone to a mailing list using their email. Technically legal, definitely annoying.',
        'replies' => 203,
    ],
    [
        'id'      => 'i5w1yxt',
        'author'  => 'CopperVault99',
        'score'   => '11.2k',
        'age'     => '10h',
        'color'   => '#ffd635',
        'text'    => 'Returning something to a store after clearly using it. The policy allows it. Your conscience shouldn\'t.',
        'replies' => 176,
    ],
    [
        'id'      => 'i5x6zpo',
        'author'  => 'SilverOrbit_7',
        'score'   => '8.9k',
        'age'     => '9h',
        'color'   => '#0dd3bb',
        'text'    => 'Asking for a raise by referencing a competing job offer you never actually applied for.',
        'replies' => 134,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>r/AskReddit — Shreddit</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --reddit-bg:      #dae0e6;
            --reddit-surface: #ffffff;
            --reddit-dark-bg: #1a1a1b;
            --reddit-dark-s:  #272729;
            --reddit-dark-c:  #1e1e1f;
            --reddit-border:  #343536;
            --reddit-orange:  #ff4500;
            --reddit-blue:    #0079d3;
            --reddit-text:    #d7dadc;
            --reddit-muted:   #818384;
            --reddit-green:   #46d160;
            --reddit-up:      #ff4500;
        }

        body {
            background: var(--reddit-dark-bg);
            color: var(--reddit-text);
            font-family: -apple-system, BlinkMacSystemFont, 'IBM Plex Sans', 'Segoe UI', sans-serif;
            min-height: 100vh;
            font-size: 14px;
        }
        .lab-badge-real {
            background: linear-gradient(90deg, #ff4500, #ff6534);
            color: #fff;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.18rem 0.5rem;
            border-radius: 3px;
            white-space: nowrap;
        }

        /* ── Site header ── */
        .site-header {
            background: var(--reddit-dark-s);
            border-bottom: 1px solid var(--reddit-border);
            height: 48px;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            gap: 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            color: var(--reddit-text);
        }
        .logo-reddit {
            width: 32px;
            height: 32px;
            background: var(--reddit-orange);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
        }
        .logo-reddit-text {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.01em;
        }
        .header-nav {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex: 1;
        }
        .header-nav a {
            color: var(--reddit-muted);
            text-decoration: none;
            font-size: 0.82rem;
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            transition: background 0.15s;
        }
        .header-nav a:hover { background: rgba(255,255,255,0.07); color: var(--reddit-text); }
        .header-search {
            background: var(--reddit-dark-bg);
            border: 1px solid var(--reddit-border);
            border-radius: 20px;
            padding: 0.35rem 1rem;
            color: var(--reddit-muted);
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 260px;
        }
        .header-right { display: flex; gap: 0.5rem; align-items: center; }
        .btn-login {
            border: 1px solid var(--reddit-blue);
            color: var(--reddit-blue);
            background: transparent;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
        }
        .btn-signup {
            background: var(--reddit-blue);
            color: #fff;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
        }

        /* ── Page layout ── */
        .page-wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1.5rem 1.5rem 3rem;
            display: grid;
            grid-template-columns: 1fr 312px;
            gap: 1.5rem;
            align-items: start;
        }
        .main-col { min-width: 0; }

        /* ── Post card ── */
        .post-card {
            background: var(--reddit-dark-s);
            border: 1px solid var(--reddit-border);
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .post-inner {
            display: flex;
            gap: 0;
        }
        .vote-col {
            background: var(--reddit-dark-c);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.75rem 0.5rem;
            gap: 0.25rem;
            border-radius: 4px 0 0 4px;
            width: 40px;
            flex-shrink: 0;
        }
        .vote-btn {
            color: var(--reddit-muted);
            font-size: 1rem;
            cursor: pointer;
            transition: color 0.15s;
        }
        .vote-btn:hover { color: var(--reddit-up); }
        .vote-count {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--reddit-text);
        }
        .post-body { padding: 0.75rem; flex: 1; min-width: 0; }
        .post-meta {
            font-size: 0.72rem;
            color: var(--reddit-muted);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            flex-wrap: wrap;
        }
        .sub-link {
            color: var(--reddit-text);
            font-weight: 700;
            text-decoration: none;
        }
        .sub-link:hover { text-decoration: underline; }
        .post-title {
            font-size: 1.1rem;
            font-weight: 500;
            color: var(--reddit-text);
            margin-bottom: 0.6rem;
            line-height: 1.4;
        }
        .post-flair {
            background: rgba(255,69,0,0.2);
            color: var(--reddit-orange);
            border: 1px solid rgba(255,69,0,0.3);
            font-size: 0.68rem;
            padding: 0.1rem 0.45rem;
            border-radius: 3px;
            font-weight: 500;
        }
        .post-actions {
            display: flex;
            gap: 0.25rem;
            margin-top: 0.75rem;
        }
        .action-btn {
            color: var(--reddit-muted);
            font-size: 0.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.5rem;
            border-radius: 2px;
            cursor: pointer;
            transition: background 0.15s;
            text-decoration: none;
        }
        .action-btn:hover { background: rgba(255,255,255,0.06); color: var(--reddit-text); }

        /* ── Comment sort bar ── */
        .sort-bar {
            background: var(--reddit-dark-s);
            border: 1px solid var(--reddit-border);
            border-radius: 4px;
            padding: 0.6rem 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            font-size: 0.8rem;
            color: var(--reddit-muted);
        }
        .sort-btn {
            color: var(--reddit-muted);
            padding: 0.3rem 0.6rem;
            border-radius: 2px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .sort-btn:hover { background: rgba(255,255,255,0.06); color: var(--reddit-text); }
        .sort-btn.active { color: var(--reddit-text); }

        /* ── Comment thread ── */
        .comment-thread { display: flex; flex-direction: column; gap: 0; }
        .comment {
            background: var(--reddit-dark-s);
            border: 1px solid var(--reddit-border);
            border-radius: 4px;
            margin-bottom: 0.5rem;
            padding: 0.75rem;
            display: flex;
            gap: 0.5rem;
        }
        .comment-vote {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.15rem;
            flex-shrink: 0;
        }
        .comment-body { flex: 1; min-width: 0; }
        .comment-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.4rem;
            flex-wrap: wrap;
        }
        .comment-author {
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none;
        }
        .comment-author:hover { text-decoration: underline; }
        .comment-score {
            font-size: 0.72rem;
            color: var(--reddit-muted);
            font-weight: 700;
        }
        .comment-age {
            font-size: 0.7rem;
            color: var(--reddit-muted);
        }
        .comment-text {
            font-size: 0.88rem;
            color: var(--reddit-text);
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }
        .comment-actions {
            display: flex;
            gap: 0.1rem;
        }
        .c-action {
            color: var(--reddit-muted);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 0.2rem 0.4rem;
            border-radius: 2px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s;
        }
        .c-action:hover { background: rgba(255,255,255,0.06); color: var(--reddit-text); }

        /*
         * VULNERABLE ELEMENT — SEE MORE BUTTON:
         * $post_id is reflected raw into an UNQUOTED id attribute.
         * No quotes needed — a space injects a new attribute.
         * Payload: t3_u9po1l onmouseover=alert(document.domain) y=
         * Hover over "See More Comments" to trigger.
         */
        .see-more-wrap {
            margin-top: 0.75rem;
        }

        /* ── Sidebar ── */
        .sidebar { position: sticky; top: 64px; }
        .sidebar-card {
            background: var(--reddit-dark-s);
            border: 1px solid var(--reddit-border);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .sidebar-header {
            background: linear-gradient(180deg, #ff4500 0%, #ff6534 100%);
            padding: 3rem 0.75rem 0.75rem;
            display: flex;
            align-items: flex-end;
            gap: 0.5rem;
        }
        .sidebar-sub-icon {
            width: 44px;
            height: 44px;
            background: var(--reddit-orange);
            border-radius: 50%;
            border: 3px solid var(--reddit-dark-s);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #fff;
            margin-top: -22px;
        }
        .sidebar-sub-name {
            font-size: 0.9rem;
            font-weight: 700;
        }
        .sidebar-body { padding: 0.75rem; }
        .sidebar-body p { font-size: 0.82rem; color: var(--reddit-muted); margin-bottom: 0.75rem; line-height: 1.5; }
        .sidebar-stat {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .stat-item { font-size: 0.78rem; }
        .stat-val { font-weight: 700; color: var(--reddit-text); }
        .stat-lbl { color: var(--reddit-muted); font-size: 0.7rem; }
        .btn-join {
            display: block;
            background: var(--reddit-blue);
            color: #fff;
            text-align: center;
            padding: 0.45rem;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            width: 100%;
            margin-bottom: 0.5rem;
        }
        .lab-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.25rem;
            margin-top: 0.65rem;
            padding-top: 0.65rem;
            border-top: 1px solid #1e293b;
        }
        .lab-meta-item { font-size: 0.72rem; color: #64748b; }
        .lab-meta-item strong { color: #94a3b8; }

        /* ── See more button ── */
        .see-more-btn {
            background: transparent;
            border: 1px solid var(--reddit-border);
            color: var(--reddit-blue);
            font-size: 0.82rem;
            font-weight: 700;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: background 0.15s;
        }
        .see-more-btn:hover { background: rgba(0,121,211,0.1); }
    </style>
</head>
<body>

    <!-- Site header -->
    <header class="site-header">
        <a href="#" class="header-logo">
            <div class="logo-reddit"><i class="bi bi-reddit"></i></div>
            <span class="logo-reddit-text">reddit</span>
        </a>
        <nav class="header-nav">
            <a href="#">Home</a>
            <a href="#">Popular</a>
            <a href="#">All</a>
        </nav>
        <div class="header-search">
            <i class="bi bi-search" style="font-size:0.85rem;"></i>
            <span>Search Reddit</span>
        </div>
        <div class="header-right">
            <a href="#" class="btn-login">Log In</a>
            <a href="#" class="btn-signup">Sign Up</a>
        </div>
    </header>

    <!-- Page -->
    <div class="page-wrap">
        <div class="main-col">

            <!-- Post card -->
            <div class="post-card">
                <div class="post-inner">
                    <div class="vote-col">
                        <i class="bi bi-arrow-up-circle-fill vote-btn" style="color:var(--reddit-up);"></i>
                        <span class="vote-count"><?php echo $post['score']; ?></span>
                        <i class="bi bi-arrow-down-circle vote-btn"></i>
                    </div>
                    <div class="post-body">
                        <div class="post-meta">
                            <a href="#" class="sub-link">r/<?php echo $safe_subreddit; ?></a>
                            <span>&bull;</span>
                            <span>Posted by u/<?php echo htmlspecialchars($post['author']); ?></span>
                            <span>&bull;</span>
                            <span><?php echo $post['age']; ?></span>
                            <span class="post-flair"><?php echo $post['flair']; ?></span>
                        </div>
                        <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                        <div class="post-actions">
                            <a href="#" class="action-btn">
                                <i class="bi bi-chat-left"></i>
                                <?php echo count($comments) + 1832; ?> Comments
                            </a>
                            <a href="#" class="action-btn"><i class="bi bi-share"></i> Share</a>
                            <a href="#" class="action-btn"><i class="bi bi-bookmark"></i> Save</a>
                            <a href="#" class="action-btn"><i class="bi bi-three-dots"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sort bar -->
            <div class="sort-bar">
                <span>Sort by:</span>
                <span class="sort-btn active"><i class="bi bi-trophy-fill" style="color:#ffd635;"></i> Top</span>
                <span class="sort-btn"><i class="bi bi-graph-up-arrow" style="color:#ff4500;"></i> Best</span>
                <span class="sort-btn"><i class="bi bi-clock-history"></i> New</span>
                <span class="sort-btn"><i class="bi bi-fire"></i> Hot</span>
            </div>

            <!-- Comments -->
            <div class="comment-thread">
                <?php foreach ($comments as $c): ?>
                <div class="comment">
                    <div class="comment-vote">
                        <i class="bi bi-arrow-up-short" style="font-size:1rem;color:var(--reddit-muted);cursor:pointer;"></i>
                        <span style="font-size:0.72rem;font-weight:700;color:var(--reddit-muted);"><?php echo htmlspecialchars($c['score']); ?></span>
                        <i class="bi bi-arrow-down-short" style="font-size:1rem;color:var(--reddit-muted);cursor:pointer;"></i>
                    </div>
                    <div class="comment-body">
                        <div class="comment-meta">
                            <a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>/svc/shreddit/api/comments/<?php echo rawurlencode($subreddit); ?>/<?php echo rawurlencode($post_id); ?>/t1_<?php echo $c['id']; ?>"
                               class="comment-author"
                               style="color:<?php echo $c['color']; ?>;">
                                u/<?php echo htmlspecialchars($c['author']); ?>
                            </a>
                            <span class="comment-score"><?php echo htmlspecialchars($c['score']); ?> points</span>
                            <span class="comment-age"><?php echo $c['age']; ?></span>
                        </div>
                        <div class="comment-text"><?php echo htmlspecialchars($c['text']); ?></div>
                        <div class="comment-actions">
                            <a href="#" class="c-action"><i class="bi bi-arrow-up-short"></i></a>
                            <a href="#" class="c-action"><i class="bi bi-arrow-down-short"></i></a>
                            <a href="#" class="c-action"><i class="bi bi-chat-left"></i> Reply</a>
                            <a href="#" class="c-action"><?php echo $c['replies']; ?> more replies</a>
                            <a href="#" class="c-action">Share</a>
                            <a href="#" class="c-action"><i class="bi bi-three-dots"></i></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- See More Comments — VULNERABLE ELEMENT -->
            <!--
                $post_id is echoed raw into an UNQUOTED id attribute.
                A space in the post_id injects a new HTML attribute directly.
                Payload: t3_u9po1l onmouseover=alert(document.domain) y=
                Hover over this button to trigger the XSS.
            -->
            <div class="see-more-wrap">
                <button id=<?php echo $post_id; ?> class="see-more-btn">
                    <i class="bi bi-chat-square-dots"></i>
                    See More Comments (1,832 remaining)
                </button>
            </div>

        </div>

        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <div class="sidebar-sub-icon"><i class="bi bi-reddit"></i></div>
                    <span class="sidebar-sub-name">r/<?php echo $safe_subreddit; ?></span>
                </div>
                <div class="sidebar-body">
                    <p>The internet's largest social platform for asking questions and getting genuine answers. Ask anything, get real answers.</p>
                    <div class="sidebar-stat">
                        <div class="stat-item">
                            <div class="stat-val">34.2M</div>
                            <div class="stat-lbl">Members</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-val">28.7k</div>
                            <div class="stat-lbl">Online</div>
                        </div>
                    </div>
                    <a href="#" class="btn-join">Join</a>
                </div>
            </div>
            <div class="sidebar-card" style="padding:0.75rem;">
                <div style="font-size:0.78rem;font-weight:700;margin-bottom:0.5rem;">Reddit Rules</div>
                <ol style="font-size:0.75rem;color:var(--reddit-muted);padding-left:1.2rem;line-height:1.8;">
                    <li>Remember the human</li>
                    <li>Behave like you would in real life</li>
                    <li>Look for the original source of content</li>
                    <li>Search for duplicates before posting</li>
                    <li>Read the community's rules</li>
                </ol>
            </div>
        </aside>
    </div>

</body>
</html>
