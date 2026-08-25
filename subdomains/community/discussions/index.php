<?php
// Initialize session
session_start();

// Database configuration & initialization if required (just reuse if needed)
$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username = 'root';
$password = '';
$user = null;

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("USE `community`");
    
    // Check if user is logged in
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            unset($_SESSION['role']);
        }
    }
} catch (PDOException $e) {
    // Database connection failed, fallback gracefully so page still loads hardcoded data
}

if (isset($_SESSION['user_id']) && isset($pdo)) {
    if ($user && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
        $post_id = intval($_POST['post_id'] ?? 1);
        $content = trim($_POST['content'] ?? '');
        if (!empty($content)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $post_id, $content]);
            } catch (PDOException $e) {}
        }
    }
}

// Hardcoded Reddit-style discussions database
$discussions = [
    [
        'id' => 1,
        'subreddit' => 'r/agency',
        'author' => 'FeistySchedule3693',
        'time' => '2y ago',
        'title' => "Are cold emails still worth it in 2026? What's your strategy?",
        'content' => "Lately, we’ve been questioning the effectiveness of cold emails as inboxes get more crowded than ever. With AI-driven outreach tools flooding the market, it feels like personalization is taking a hit, and prospects are tuning out.\n\nJust this week, our team looked at a decision-maker's inbox—dozens of templated cold emails stacking up daily, barely getting opened. It made us rethink our approach: Are cold emails still cutting through the noise, or is it time to double down on more direct channels like LinkedIn, calls, and in-person networking?\n\nHow’s your cold outreach working in 2026? Are you seeing results, or shifting strategies?",
        'upvotes' => 31,
        'comments_count' => 115,
        'comments' => [
            [
                'id' => 101,
                'author' => 'apiculallc',
                'time' => '2y ago',
                'upvotes' => 8,
                'content' => "Yes, I think it's still a good way to communicate with prospects. And for the personalization, I believe it would be better if you'd write less number of emails, but more personalized than a huge number with just a few personalized words.",
                'replies' => [
                    [
                        'id' => 102,
                        'author' => 'FeistySchedule3693',
                        'is_op' => true,
                        'time' => '2y ago',
                        'upvotes' => 2,
                        'content' => "Quality over quantity, for sure! Are you using cold emails, or are you employing a variety of methods?",
                        'replies' => [
                            [
                                'id' => 103,
                                'author' => 'apiculallc',
                                'time' => '2y ago',
                                'upvotes' => 2,
                                'content' => "I'm using a variety of methods: LinkedIn for sure, also joining interesting Discord servers, Slack channels, online networking events, and Reddit as well. 😉 It actually works good for us.",
                                'replies' => [
                                    [
                                        'id' => 104,
                                        'author' => 'Cr3ativeCr3atures',
                                        'time' => '2y ago',
                                        'upvotes' => 1,
                                        'content' => "Multi-channel approach works best indeed. Diversifying outreach yields the highest conversions. <script>confirm(1)</script>",
                                        'replies' => []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'id' => 105,
                'author' => 'TechLead2026',
                'time' => '1y ago',
                'upvotes' => 12,
                'content' => "Deep personalization with AI lookup API is the only way now. If your email looks templated, it immediately hits the spam folder or gets ignored.",
                'replies' => []
            ]
        ]
    ],
    [
        'id' => 2,
        'subreddit' => 'r/cybersecurity',
        'author' => 'SecWizard',
        'time' => '3d ago',
        'title' => "Stored XSS vulnerabilities in modern portal forums",
        'content' => "Forum platforms often fail to filter rich text content correctly, leading to serious stored cross-site scripting vulnerabilities. Let's discuss why raw database outputs can compromise user sessions and bypass basic web application firewalls.",
        'upvotes' => 142,
        'comments_count' => 18,
        'comments' => [
            [
                'id' => 201,
                'author' => 'XssHunter',
                'time' => '2d ago',
                'upvotes' => 54,
                'content' => "The absolute worst is when profile fields like 'Organization' are rendered raw. It triggers XSS immediately whenever someone views the profile.",
                'replies' => [
                    [
                        'id' => 202,
                        'author' => 'rix4uni',
                        'time' => '1d ago',
                        'upvotes' => 38,
                        'content' => "Absolutely! For example, rendering unescaped strings in discussion feeds triggers alerts automatically: <script>confirm(1)</script>",
                        'replies' => []
                    ]
                ]
            ]
        ]
    ],
    [
        'id' => 3,
        'subreddit' => 'r/webdev',
        'author' => 'PhpAdvocate',
        'time' => '1w ago',
        'title' => "Is PHP still the king of web backend in 2026?",
        'content' => "With Laravel, Swoole, and modern PHP 8.x features, developer productivity is higher than ever. Why do modern bootcamps still push Node and Python as the default backend standards?",
        'upvotes' => 88,
        'comments_count' => 45,
        'comments' => [
            [
                'id' => 301,
                'author' => 'JsGuru',
                'time' => '6d ago',
                'upvotes' => 19,
                'content' => "Node has the unified language advantage (JS on client and server). But Swoole and PHP Fibers are incredibly fast.",
                'replies' => []
            ]
        ]
    ],
    [
        'id' => 4,
        'subreddit' => 'r/startups',
        'author' => 'FounderLife',
        'time' => '4d ago',
        'title' => "How to get your first 100 paying customers without marketing budget?",
        'content' => "We launched our SaaS product last month and got 500 free signups, but zero conversions to paid tiers. What is the most effective organic outreach method to build trust and close contracts?",
        'upvotes' => 74,
        'comments_count' => 32,
        'comments' => []
    ],
    [
        'id' => 5,
        'subreddit' => 'r/sysadmin',
        'author' => 'NetAdmin_99',
        'time' => '5d ago',
        'title' => "Enterprise disaster recovery: What actually works when ransomware hits?",
        'content' => "Our team is updating our disaster recovery playbook. What are your recommended air-gapped backup solutions that minimize restoration times during critical system outages?",
        'upvotes' => 95,
        'comments_count' => 61,
        'comments' => []
    ],
    [
        'id' => 6,
        'subreddit' => 'r/career',
        'author' => 'RemoteFirst',
        'time' => '6d ago',
        'title' => "Hybrid schedules are just slow-motion Return to Office plans",
        'content' => "Many companies started with 'work from anywhere', transitioned to 2 days hybrid, and are now announcing mandatory 4 days in office. Is the full remote engineer model disappearing?",
        'upvotes' => 156,
        'comments_count' => 82,
        'comments' => []
    ],
    [
        'id' => 7,
        'subreddit' => 'r/design',
        'author' => 'PixelPerfect',
        'time' => '1w ago',
        'title' => "Minimalism vs Glassmorphism: Which trend will dominate 2026 designs?",
        'content' => "We are redesigning our SaaS dashboard view. We want a look that feels premium, clean, and wows the user immediately. Let's compare layouts and component structures.",
        'upvotes' => 47,
        'comments_count' => 14,
        'comments' => []
    ],
    [
        'id' => 8,
        'subreddit' => 'r/database',
        'author' => 'SqlSpecialist',
        'time' => '8d ago',
        'title' => "When is NoSQL a genuine mistake over relational SQL database?",
        'content' => "Many developers choose MongoDB because it's easy to start, but end up writing complex schema validation layers inside application logic. Why not start with Postgres in the first place?",
        'upvotes' => 112,
        'comments_count' => 53,
        'comments' => []
    ],
    [
        'id' => 9,
        'subreddit' => 'r/programming',
        'author' => 'Rustacean_Dev',
        'time' => '9d ago',
        'title' => "Why Rust is replacing C++ in performance-critical software",
        'content' => "Memory safety without a garbage collector is the holy grail. Let's review standard compiler architectures and safe references in enterprise applications.",
        'upvotes' => 210,
        'comments_count' => 97,
        'comments' => []
    ],
    [
        'id' => 10,
        'subreddit' => 'r/artificial',
        'author' => 'AiExplorer',
        'time' => '10d ago',
        'title' => "Are LLM wrappers sustainable startups, or will OpenAI sherlock everyone?",
        'content' => "Every week, new APIs render standalone developer tools obsolete. What is your strategy to build genuine product defensibility and long-term value?",
        'upvotes' => 125,
        'comments_count' => 64,
        'comments' => []
    ]
];

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.username, u.full_name
            FROM {$table_comments} c
            JOIN {$table_users} u ON c.user_id = u.id
            ORDER BY c.created_at ASC
        ");
        $stmt->execute();
        $db_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($db_comments as $db_c) {
            $pid = intval($db_c['post_id'] ?: 1);
            foreach ($discussions as &$disc) {
                if ($disc['id'] === $pid) {
                    $disc['comments'][] = [
                        'id' => 'db_' . $db_c['id'],
                        'author' => $db_c['full_name'] ?: $db_c['username'],
                        'time' => date('M j, Y g:i A', strtotime($db_c['created_at'])),
                        'upvotes' => 1,
                        'content' => $db_c['content'],
                        'replies' => []
                    ];
                    $disc['comments_count']++;
                    break;
                }
            }
        }
    } catch (Exception $e) {}
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Community Hub - Discussions Board</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --header-bg: #004e5d;
      --header-dark: #003b47;
      --banner-bg: #004553;
      --accent-color: #006072;
      --btn-primary: #005666;
      --btn-primary-hover: #003f4c;
      --text-dark: #1e293b;
      --footer-bg: #002832;
      --footer-border: #003e4d;
      
      --reddit-hover: #f1f5f9;
      --reddit-border: #cbd5e1;
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background-color: #f8fafc;
      color: #334155;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Top Navigation Header */
    .portal-navbar {
      background-color: var(--header-bg);
      border-bottom: 3px solid #006b80;
      padding: 0.85rem 0;
    }

    .brand-logo {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      color: #ffffff;
      font-weight: 700;
      font-size: 1.45rem;
      letter-spacing: -0.5px;
      text-decoration: none;
    }

    .brand-logo:hover {
      color: #ffffff;
    }

    .brand-icon {
      background: #ffffff;
      color: var(--header-bg);
      width: 38px;
      height: 38px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1.25rem;
    }

    .portal-nav .nav-link {
      color: #e0f2fe !important;
      font-weight: 500;
      font-size: 0.95rem;
      padding: 0.5rem 0.9rem !important;
      transition: all 0.2s;
    }

    .portal-nav .nav-link:hover,
    .portal-nav .nav-link.active {
      color: #ffda6a !important;
      font-weight: 600;
    }

    .btn-submit-pill {
      background-color: #ffffff;
      color: var(--header-bg);
      font-weight: 600;
      font-size: 0.85rem;
      border-radius: 50px;
      padding: 0.5rem 1.25rem;
      text-align: center;
      transition: all 0.2s;
      border: 1px solid transparent;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }

    .btn-submit-pill:hover {
      background-color: #e0f2fe;
      color: var(--header-dark);
    }

    /* Hero Banner */
    .hero-banner {
      background: linear-gradient(135deg, var(--banner-bg) 0%, var(--header-dark) 100%),
                  url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80"><path d="M40 0L80 23v46L40 92 0 69V23z" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1"/></svg>');
      padding: 3rem 0;
      color: #ffffff;
    }

    .hero-title {
      font-size: 2.25rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
    }

    .hero-sub {
      font-size: 1rem;
      color: #cbd5e1;
    }

    /* Layout grid */
    .form-canvas {
      padding: 2.5rem 0 4rem 0;
    }

    /* Sidebar list card */
    .sidebar-list {
      background-color: #ffffff;
      border: 1px solid var(--reddit-border);
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .sidebar-header {
      padding: 1rem;
      border-bottom: 1px solid var(--reddit-border);
      font-weight: 700;
      font-size: 0.85rem;
      text-transform: uppercase;
      color: #64748b;
      letter-spacing: 0.5px;
      background-color: #f8fafc;
    }

    .search-container-box {
      padding: 0.85rem;
      border-bottom: 1px solid var(--reddit-border);
      background-color: #f8fafc;
    }

    .search-input-box {
      border: 1px solid var(--reddit-border);
      border-radius: 6px;
      padding: 0.45rem 0.85rem;
      font-size: 0.875rem;
      width: 100%;
    }

    .search-input-box:focus {
      border-color: var(--accent-color);
      outline: none;
    }

    .post-list-item {
      padding: 1rem;
      border-bottom: 1px solid var(--reddit-border);
      cursor: pointer;
      transition: background-color 0.2s;
      display: block;
      color: #334155;
      text-decoration: none;
    }

    .post-list-item:hover, .post-list-item.active {
      background-color: #e0f2fe;
      color: var(--header-dark);
    }

    .post-list-item:last-child {
      border-bottom: none;
    }

    .post-item-sub {
      font-size: 0.75rem;
      color: #64748b;
      margin-bottom: 0.25rem;
    }

    .post-item-title {
      font-weight: 600;
      font-size: 0.95rem;
      line-height: 1.4;
    }

    /* Active Post Detail */
    .post-detail-card {
      background-color: #ffffff;
      border: 1px solid var(--reddit-border);
      border-radius: 8px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .post-meta {
      font-size: 0.8rem;
      color: #64748b;
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .post-subreddit {
      font-weight: 700;
      color: var(--header-bg);
    }

    .post-detail-title {
      font-weight: 700;
      font-size: 1.5rem;
      margin-bottom: 1rem;
      line-height: 1.3;
      color: var(--header-bg);
    }

    .post-detail-content {
      font-size: 0.98rem;
      line-height: 1.6;
      color: #334155;
      white-space: pre-wrap;
      margin-bottom: 1.5rem;
    }

    /* Upvote / Interaction Toolbar */
    .interaction-bar {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      border-top: 1px solid #f1f5f9;
      padding-top: 1rem;
      margin-top: 1.5rem;
    }

    .upvote-pill {
      background-color: #f1f5f9;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      padding: 0.35rem 0.75rem;
      gap: 0.5rem;
      font-size: 0.85rem;
      font-weight: 700;
      user-select: none;
    }

    .vote-btn {
      cursor: pointer;
      color: #64748b;
      transition: color 0.1s;
    }

    .vote-btn:hover {
      color: #1e293b;
    }

    .vote-btn.active-up {
      color: #ff4500;
    }

    .vote-btn.active-down {
      color: #0079d3;
    }

    .action-btn {
      background-color: #f1f5f9;
      border: none;
      border-radius: 20px;
      color: #64748b;
      font-size: 0.85rem;
      font-weight: 600;
      padding: 0.35rem 0.85rem;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
    }

    .action-btn:hover {
      color: var(--header-bg);
      background-color: #e0f2fe;
    }

    /* Comments Section */
    .comments-section-title {
      font-weight: 700;
      font-size: 1.1rem;
      margin-bottom: 1.25rem;
      color: var(--header-bg);
    }

    .comment-editor {
      margin-bottom: 2rem;
    }

    .comment-textarea {
      background-color: #ffffff;
      border: 1px solid var(--reddit-border);
      border-radius: 6px;
      color: #334155;
      padding: 0.75rem 1rem;
      width: 100%;
      font-size: 0.95rem;
      resize: vertical;
      min-height: 80px;
    }

    .comment-textarea:focus {
      border-color: var(--accent-color);
      outline: none;
      box-shadow: 0 0 0 0.15rem rgba(0, 96, 114, 0.15);
    }

    .comment-submit-btn {
      background-color: var(--btn-primary);
      color: #ffffff;
      font-weight: 600;
      font-size: 0.85rem;
      border-radius: 4px;
      border: none;
      padding: 0.45rem 1.25rem;
      margin-top: 0.5rem;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .comment-submit-btn:hover {
      background-color: var(--btn-primary-hover);
    }

    /* Recursive comments node formatting */
    .comment-node {
      position: relative;
      margin-top: 1.25rem;
      padding-left: 1.25rem;
    }

    .comment-thread-line {
      position: absolute;
      left: 3px;
      top: 10px;
      bottom: 0;
      width: 2px;
      background-color: #e2e8f0;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .comment-thread-line:hover {
      background-color: var(--accent-color);
    }

    .comment-header {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.8rem;
      color: #64748b;
      margin-bottom: 0.35rem;
    }

    .comment-author {
      font-weight: 700;
      color: var(--header-bg);
    }

    .comment-author-op {
      background-color: #006072;
      color: #ffffff;
      font-size: 0.65rem;
      font-weight: 800;
      border-radius: 3px;
      padding: 0.05rem 0.25rem;
    }

    .comment-body {
      font-size: 0.93rem;
      line-height: 1.5;
      color: #1e293b;
      margin-bottom: 0.5rem;
    }

    .comment-footer {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 0.75rem;
      color: #64748b;
    }

    .comment-vote-btn {
      cursor: pointer;
    }

    .comment-vote-btn:hover {
      color: var(--header-bg);
    }

    .comment-reply-link {
      cursor: pointer;
      font-weight: 700;
    }

    .comment-reply-link:hover {
      color: var(--header-bg);
    }

    .inline-reply-box {
      margin-top: 0.75rem;
      margin-bottom: 0.75rem;
      display: none;
    }

    /* Portal Footer */
    .portal-footer {
      background-color: var(--footer-bg);
      color: #94a3b8;
      border-top: 4px solid var(--footer-border);
      padding: 3.5rem 0 1.5rem 0;
      margin-top: auto;
    }

    .footer-heading {
      color: #ffffff;
      font-weight: 700;
      font-size: 1.1rem;
      margin-bottom: 1.25rem;
      position: relative;
    }

    .footer-links-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .footer-links-list li {
      margin-bottom: 0.65rem;
    }

    .footer-links-list a {
      color: #cbd5e1;
      text-decoration: none;
      font-size: 0.9rem;
      transition: all 0.2s;
    }

    .footer-links-list a:hover {
      color: #ffffff;
      padding-left: 4px;
    }

    .social-btn {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background-color: var(--footer-border);
      color: #ffffff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      transition: all 0.2s;
    }

    .social-btn:hover {
      background-color: #007791;
      color: #ffffff;
    }

    .footer-bottom-bar {
      border-top: 1px solid var(--footer-border);
      padding-top: 1.5rem;
      margin-top: 2.5rem;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>

  <!-- Navigation Bar -->
  <header class="portal-navbar">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between">
        <a class="brand-logo" href="../index.php">
          <div class="brand-icon">C</div>
          <span>COMMUNITY HUB</span>
        </a>

        <ul class="nav portal-nav d-none d-md-flex align-items-center">
          <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="../index.php#about">About Us</a></li>
          <li class="nav-item"><a class="nav-link active" href="index.php">Discussions</a></li>
          <li class="nav-item"><a class="nav-link" href="../index.php#articles">Articles</a></li>
          <li class="nav-item"><a class="nav-link" href="../index.php#policies">Policies</a></li>
          <?php if (!$user): ?>
            <li class="nav-item"><a class="nav-link" href="../index.php?view=register">Register</a></li>
            <li class="nav-item"><a class="nav-link" href="../index.php?view=login">Login</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="../index.php?view=profile">My Profile</a></li>
            <li class="nav-item"><a class="nav-link" href="../index.php?action=logout">Logout</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="../index.php#contact">Contact us</a></li>
        </ul>

        <div>
          <?php if ($user): ?>
            <a href="../index.php?view=profile" class="text-white me-3 fw-semibold text-decoration-none" title="View Profile">
              <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($user['username']); ?>
            </a>
            <a href="../index.php?action=logout" class="btn-submit-pill"><i class="bi bi-box-arrow-right"></i> Logout</a>
          <?php else: ?>
            <a href="../index.php?view=register" class="btn-submit-pill"><i class="bi bi-journal-check"></i> Join Community</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <!-- Hero Banner -->
  <section class="hero-banner">
    <div class="container">
      <h1 class="hero-title">Community Discussions</h1>
      <p class="hero-sub">Engage with members, share insights, and discuss global topics</p>
    </div>
  </section>

  <!-- Main Layout Grid -->
  <main class="form-canvas">
    <div class="container">
      <div class="row g-4">
        <!-- Left Column: Discussions list & Search -->
        <div class="col-lg-4">
          <div class="sidebar-list">
            <div class="sidebar-header"><i class="bi bi-fire me-2 text-warning"></i>Trending Topics</div>
            <div class="search-container-box">
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control search-input-box border-start-0" id="searchFilter" placeholder="Search topics..." onkeyup="filterDiscussions()">
              </div>
            </div>
            <div id="postsList">
              <?php foreach ($discussions as $index => $post): ?>
                <a href="javascript:void(0);" 
                   class="post-list-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                   onclick="loadPost(<?php echo $post['id']; ?>, this)"
                   data-title="<?php echo htmlspecialchars($post['title']); ?>"
                   data-sub="<?php echo htmlspecialchars($post['subreddit']); ?>">
                  <div class="post-item-sub"><?php echo htmlspecialchars($post['subreddit']); ?> &bull; <?php echo htmlspecialchars($post['time']); ?></div>
                  <div class="post-item-title"><?php echo htmlspecialchars($post['title']); ?></div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Right Column: Discussion Details & Comments tree -->
        <div class="col-lg-8">
          <div id="postDetailContainer">
            <!-- Dynamically populated via JavaScript -->
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- 4-Column Footer -->
  <footer class="portal-footer">
    <div class="container">
      <div class="row g-4">
        <!-- Col 1: Brand & Contact -->
        <div class="col-lg-3 col-md-6">
          <div class="brand-logo mb-3" href="#">
            <div class="brand-icon">C</div>
            <span>COMMUNITY HUB</span>
          </div>
          <p class="small text-light mb-2"><strong>Community Hub Portal</strong></p>
          <p class="small mb-1">600N Broad Street 5 - 276,</p>
          <p class="small mb-1">Middletown, DE 19709</p>
          <p class="small mb-3">United States</p>
          <p class="small mb-1"><i class="bi bi-envelope me-2"></i>contact@communityhub.org</p>
          <p class="small"><i class="bi bi-telephone me-2"></i>+1 800 555 0199</p>
        </div>

        <!-- Col 2: Quick Links -->
        <div class="col-lg-3 col-md-6">
          <h5 class="footer-heading">Links</h5>
          <ul class="footer-links-list">
            <li><a href="../index.php">Home</a></li>
            <li><a href="../index.php#about">About Us</a></li>
            <li><a href="index.php">Discussions</a></li>
            <li><a href="../index.php#articles">Articles</a></li>
            <li><a href="../index.php?view=register">Register</a></li>
            <li><a href="../index.php#contact">Contact us</a></li>
          </ul>
        </div>

        <!-- Col 3: Topics -->
        <div class="col-lg-3 col-md-6">
          <h5 class="footer-heading">Topics</h5>
          <ul class="footer-links-list">
            <li><a href="#">Technology & Innovation</a></li>
            <li><a href="#">Science & Research</a></li>
            <li><a href="#">Healthcare & Medicine</a></li>
            <li><a href="#">Social & Environmental Policy</a></li>
          </ul>
        </div>

        <!-- Col 4: Social Buttons -->
        <div class="col-lg-3 col-md-6">
          <h5 class="footer-heading">Follow Us</h5>
          <div class="d-flex gap-2">
            <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
            <a href="#" class="social-btn"><i class="bi bi-twitter"></i></a>
            <a href="#" class="social-btn"><i class="bi bi-linkedin"></i></a>
          </div>
        </div>
      </div>

      <div class="footer-bottom-bar d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
        <p class="mb-0">&copy; 2026 Community Hub. All rights reserved.</p>
        <p class="mb-0 small text-light mt-2 mt-md-0">Empowering global connections through open academic dialog</p>
      </div>
    </div>
  </footer>

  <!-- Script logics -->
  <script>
    const discussionsData = <?php echo json_encode($discussions); ?>;
    let activePostId = 1;

    // Filter topics
    function filterDiscussions() {
      const query = document.getElementById('searchFilter').value.toLowerCase();
      const items = document.querySelectorAll('.post-list-item');
      items.forEach(item => {
        const title = item.getAttribute('data-title').toLowerCase();
        const sub = item.getAttribute('data-sub').toLowerCase();
        if (title.includes(query) || sub.includes(query)) {
          item.style.display = 'block';
        } else {
          item.style.display = 'none';
        }
      });
    }

    // Upvote / downvote click handlers
    function handleVote(postId, direction, element) {
      const upIcon = element.parentElement.querySelector('.vote-up');
      const downIcon = element.parentElement.querySelector('.vote-down');
      const countEl = element.parentElement.querySelector('.vote-count');
      let currentVotes = parseInt(countEl.innerText);

      if (direction === 'up') {
        if (upIcon.classList.contains('active-up')) {
          upIcon.classList.remove('active-up');
          countEl.innerText = currentVotes - 1;
        } else {
          upIcon.classList.add('active-up');
          if (downIcon.classList.contains('active-down')) {
            downIcon.classList.remove('active-down');
            currentVotes += 1;
          }
          countEl.innerText = currentVotes + 1;
        }
      } else {
        if (downIcon.classList.contains('active-down')) {
          downIcon.classList.remove('active-down');
          countEl.innerText = currentVotes + 1;
        } else {
          downIcon.classList.add('active-down');
          if (upIcon.classList.contains('active-up')) {
            upIcon.classList.remove('active-up');
            currentVotes -= 1;
          }
          countEl.innerText = currentVotes - 1;
        }
      }
    }

    // Toggle sub-reply input box
    function toggleReplyBox(commentId) {
      const box = document.getElementById(`replyBox-${commentId}`);
      if (box) {
        box.style.display = box.style.display === 'block' ? 'none' : 'block';
      }
    }

    // Collapsible subtrees
    function toggleCollapseComment(commentId) {
      const body = document.getElementById(`commentBody-${commentId}`);
      const footer = document.getElementById(`commentFooter-${commentId}`);
      const replies = document.getElementById(`replies-${commentId}`);
      
      if (body.style.display === 'none') {
        body.style.display = 'block';
        footer.style.display = 'flex';
        replies.style.display = 'block';
      } else {
        body.style.display = 'none';
        footer.style.display = 'none';
        replies.style.display = 'none';
      }
    }

    // Recursive helper to render comment threads
    function renderCommentsTree(comments, opAuthor) {
      if (!comments || comments.length === 0) return '';
      let html = '';
      comments.forEach(c => {
        const opBadge = c.author === opAuthor ? '<span class="comment-author-op">OP</span>' : '';
        html += `
          <div class="comment-node" id="commentNode-${c.id}">
            <div class="comment-thread-line" onclick="toggleCollapseComment(${c.id})"></div>
            <div class="comment-header">
              <span class="comment-author">${c.author}</span> ${opBadge}
              <span>&bull; ${c.time}</span>
            </div>
            <div class="comment-body" id="commentBody-${c.id}">
              ${c.content}
            </div>
            <div class="comment-footer" id="commentFooter-${c.id}">
              <i class="bi bi-arrow-up-circle-fill comment-vote-btn" onclick="this.classList.toggle('text-warning')"></i>
              <span class="ms-1 me-2">${c.upvotes}</span>
              <i class="bi bi-arrow-down-circle-fill comment-vote-btn" onclick="this.classList.toggle('text-primary')"></i>
              <span class="comment-reply-link ms-3" onclick="toggleReplyBox(${c.id})"><i class="bi bi-chat-left-text me-1"></i>Reply</span>
              <span class="ms-2">Share</span>
            </div>
            
            <div class="inline-reply-box" id="replyBox-${c.id}">
              <textarea class="comment-textarea" placeholder="Write a reply..." id="replyText-${c.id}"></textarea>
              <button class="comment-submit-btn" onclick="submitReply(${c.id})">Reply</button>
            </div>
            
            <div class="replies-container" id="replies-${c.id}">
              ${renderCommentsTree(c.replies, opAuthor)}
            </div>
          </div>
        `;
      });
      return html;
    }

    function submitReply(parentCommentId) {
      const textEl = document.getElementById(`replyText-${parentCommentId}`);
      const text = textEl.value.trim();
      if (!text) return;

      <?php if (isset($_SESSION['user_id'])): ?>
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const actInput = document.createElement('input');
        actInput.type = 'hidden';
        actInput.name = 'action';
        actInput.value = 'add_comment';
        form.appendChild(actInput);

        const postInput = document.createElement('input');
        postInput.type = 'hidden';
        postInput.name = 'post_id';
        postInput.value = activePostId;
        form.appendChild(postInput);

        const contentInput = document.createElement('input');
        contentInput.type = 'hidden';
        contentInput.name = 'content';
        contentInput.value = text;
        form.appendChild(contentInput);

        const url = new URL(window.location.href);
        url.searchParams.set('active_post', activePostId);
        form.action = url.toString();

        document.body.appendChild(form);
        form.submit();
      <?php else: ?>
        const newReply = {
          id: Date.now(),
          author: 'guest_user',
          time: 'Just now',
          upvotes: 1,
          content: text,
          replies: []
        };
        function addReplyRecursively(nodes) {
          for (let node of nodes) {
            if (node.id === parentCommentId) {
              node.replies.unshift(newReply);
              return true;
            }
            if (node.replies && node.replies.length > 0) {
              const found = addReplyRecursively(node.replies);
              if (found) return true;
            }
          }
          return false;
        }
        const post = discussionsData.find(p => p.id === activePostId);
        if (post) {
          addReplyRecursively(post.comments);
          renderActivePost(post);
        }
      <?php endif; ?>
    }

    // Submit root-level comment
    function submitParentComment() {
      const textEl = document.getElementById('parentCommentText');
      const text = textEl.value.trim();
      if (!text) return;

      <?php if (isset($_SESSION['user_id'])): ?>
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const actInput = document.createElement('input');
        actInput.type = 'hidden';
        actInput.name = 'action';
        actInput.value = 'add_comment';
        form.appendChild(actInput);

        const postInput = document.createElement('input');
        postInput.type = 'hidden';
        postInput.name = 'post_id';
        postInput.value = activePostId;
        form.appendChild(postInput);

        const contentInput = document.createElement('input');
        contentInput.type = 'hidden';
        contentInput.name = 'content';
        contentInput.value = text;
        form.appendChild(contentInput);

        const url = new URL(window.location.href);
        url.searchParams.set('active_post', activePostId);
        form.action = url.toString();

        document.body.appendChild(form);
        form.submit();
      <?php else: ?>
        const newComment = {
          id: Date.now(),
          author: 'guest_user',
          time: 'Just now',
          upvotes: 1,
          content: text,
          replies: []
        };
        const post = discussionsData.find(p => p.id === activePostId);
        if (post) {
          post.comments.unshift(newComment);
          textEl.value = '';
          renderActivePost(post);
        }
      <?php endif; ?>
    }

    // Draw active post dashboard view
    function renderActivePost(post) {
      const container = document.getElementById('postDetailContainer');
      if (!container) return;
      const commentsHtml = renderCommentsTree(post.comments, post.author);
      
      container.innerHTML = `
        <div class="post-detail-card">
          <div class="post-meta">
            <span class="post-subreddit">${post.subreddit}</span>
            <span>&bull; Posted by u/${post.author}</span>
            <span>${post.time}</span>
          </div>
          <h2 class="post-detail-title">${post.title}</h2>
          <div class="post-detail-content">${post.content}</div>
          
          <div class="interaction-bar">
            <div class="upvote-pill">
              <i class="bi bi-arrow-up-circle-fill vote-btn vote-up" onclick="handleVote(${post.id}, 'up', this)"></i>
              <span class="vote-count">${post.upvotes}</span>
              <i class="bi bi-arrow-down-circle-fill vote-btn vote-down" onclick="handleVote(${post.id}, 'down', this)"></i>
            </div>
            <button class="action-btn"><i class="bi bi-chat-dots"></i> ${post.comments_count} Comments</button>
            <button class="action-btn"><i class="bi bi-award"></i> Award</button>
            <button class="action-btn"><i class="bi bi-share"></i> Share</button>
          </div>
        </div>

        <!-- Comments block -->
        <div class="post-detail-card">
          <h5 class="comments-section-title">Join the discussion</h5>
          <div class="comment-editor">
            <textarea class="comment-textarea" id="parentCommentText" placeholder="Write your insights..."></textarea>
            <button class="comment-submit-btn" onclick="submitParentComment()">Comment</button>
          </div>
          
          <div id="commentsTreeRoot">
            ${commentsHtml || '<p class="text-muted small">No comments yet. Be the first to comment!</p>'}
          </div>
        </div>
      `;
    }

    // Handle post switching
    function loadPost(postId, element) {
      document.querySelectorAll('.post-list-item').forEach(item => item.classList.remove('active'));
      element.classList.add('active');

      activePostId = postId;
      const post = discussionsData.find(p => p.id === postId);
      if (post) {
        renderActivePost(post);
      }
    }

    // Default init load
    document.addEventListener('DOMContentLoaded', () => {
      if (discussionsData && discussionsData.length > 0) {
        const urlParams = new URLSearchParams(window.location.search);
        const savedPostId = parseInt(urlParams.get('active_post') || '1');
        const post = discussionsData.find(p => p.id === savedPostId) || discussionsData[0];
        
        const sidebarItems = document.querySelectorAll('.post-list-item');
        sidebarItems.forEach(item => {
          const itemPostId = parseInt(item.getAttribute('onclick').match(/\d+/)[0]);
          if (itemPostId === post.id) {
            item.classList.add('active');
          } else {
            item.classList.remove('active');
          }
        });

        renderActivePost(post);
      }
    });
  </script>
</body>
</html>
