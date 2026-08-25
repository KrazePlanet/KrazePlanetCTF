<?php
/**
 * ============================================================================
 * ButterCMS / DevArticles Community & Review Platform — Real-World Lab
 * 
 * Features for SQLi Testing:
 *   1. User Registration & Login (Auth Bypass / Second-Order SQLi)
 *   2. Discussion Comment Submission (INSERT SQLi - Authenticated Users Only)
 *   3. Comment Sorting & Filtering (ORDER BY SQLi)
 *   4. Newsletter Subscription (Blind INSERT SQLi)
 * ============================================================================
 */

session_start();

// ── Database Configuration ──────────────────────────────────────────────────
$db_host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$db_user = "root";
$db_pass = "";
$db_name = "KrazePlanet_DB";

$conn = @mysqli_connect($db_host, $db_user, $db_pass);
if (!$conn) {
    die("<div style='font-family:sans-serif;padding:30px;background:#f8d7da;color:#721c24;margin:50px auto;max-width:600px;border-radius:8px;'><h3>Database Connection Error</h3><p>Could not connect to MySQL server. Please ensure XAMPP/LAMPP MySQL is running.</p><p><code>" . htmlspecialchars(mysqli_connect_error()) . "</code></p></div>");
}

@mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $db_name");
@mysqli_select_db($conn, $db_name);

// ── Schema Initialization ───────────────────────────────────────────────────
function setup_reviews_schema($conn) {
    // 1. Review Users Table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS review_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        website VARCHAR(200) DEFAULT '',
        role VARCHAR(50) DEFAULT 'member',
        avatar_color VARCHAR(20) DEFAULT '#2563eb',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed default users if empty
    $chk_u = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM review_users");
    if ($chk_u && ($row = mysqli_fetch_assoc($chk_u)) && $row['c'] == 0) {
        $p1 = password_hash('author2026', PASSWORD_DEFAULT);
        $p2 = password_hash('rakunaco123', PASSWORD_DEFAULT);
        $p3 = password_hash('shantanu99', PASSWORD_DEFAULT);
        @mysqli_query($conn, "INSERT INTO review_users (name, email, password, website, role, avatar_color) VALUES
            ('Jake Lumetta', 'jake@buttercms.com', '$p1', 'https://buttercms.com', 'admin', '#ec4899'),
            ('rakunaco', 'rakunaco@devmail.io', '$p2', 'https://rakunaco.dev', 'member', '#ef4444'),
            ('Shantanu Sharma', 'shantanu@sharma.in', '$p3', 'https://shantanusharma.me', 'member', '#3b82f6')");
    }

    // 2. Comments / Reviews Table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS review_comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        website VARCHAR(200) DEFAULT '',
        comment TEXT NOT NULL,
        likes INT DEFAULT 0,
        parent_id INT DEFAULT 0,
        reply_to VARCHAR(100) DEFAULT '',
        avatar_color VARCHAR(20) DEFAULT '#6b7280',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed initial discussion comments if empty
    $chk_c = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM review_comments");
    if ($chk_c && ($row = mysqli_fetch_assoc($chk_c)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO review_comments (user_id, name, email, website, comment, likes, parent_id, reply_to, avatar_color, created_at) VALUES
            (2, 'rakunaco', 'rakunaco@devmail.io', 'https://rakunaco.dev', 'Totally agree. We have researched many other options and Butter is the only one (in our opinion) that provides ease of implementation & optimal SEO on Heroku.', 2, 0, '', '#ef4444', DATE_SUB(NOW(), INTERVAL 35 DAY)),
            (1, 'Publicalog - Quadcopter Drones', 'contact@publicalog.com', 'https://publicalog.com', 'Its so hard to explain this to clients, but technically is simple. The problem is when you need to use a subdomain for a technical reason like helpcenter or similar. some website use subdomain for blog becouse they cant have a blog in a custom CMS, after showing how important is then is possible to do all the technical work for making this happend. Thanks for showing this great proof.', 1, 0, '', '#f59e0b', DATE_SUB(NOW(), INTERVAL 18 DAY)),
            (3, 'Shantanu Sharma', 'shantanu@sharma.in', 'https://shantanusharma.me', 'Hey Jake ! thanks for such a experimental post. But still i want your opinion, whether i can host on subdomain or on subdirectories ?\n\nis there any complication if i use CMS on subdomains as mu root domain is on HTML/CSS coded.', 0, 0, '', '#3b82f6', DATE_SUB(NOW(), INTERVAL 6 DAY)),
            (1, 'Jake Lumetta', 'jake@buttercms.com', 'https://buttercms.com', 'You _can_ use CMS on a subdomain, but if you\'re creating content that needs to rank well for SEO, it\'s not the optimal set up.', 4, 3, 'Shantanu Sharma', '#ec4899', DATE_SUB(NOW(), INTERVAL 5 DAY))");
    }

    // 3. Newsletter Subscribers Table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS review_newsletter (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Secret CTF Flag & Dev Keys Table
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS review_vault (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(100) NOT NULL,
        key_value VARCHAR(255) NOT NULL,
        notes VARCHAR(255) NOT NULL
    )");

    $chk_v = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM review_vault");
    if ($chk_v && ($row = mysqli_fetch_assoc($chk_v)) && $row['c'] == 0) {
        @mysqli_query($conn, "INSERT INTO review_vault (key_name, key_value, notes) VALUES
            ('FLAG_REVIEWS_SQLI', 'FLAG{bI0g_c0mm3nt_1ns3rt_sql1_2026}', 'Target CTF Flag for reviews lab'),
            ('BUTTERCMS_PROD_API_KEY', 'live_sec_993412aa89f012bb45', 'Internal production API token'),
            ('DISQUS_SECRET_KEY', 'disqus_sec_498214fa76129a', 'Single sign-on secret')");
    }
}
setup_reviews_schema($conn);

// ── State & Messages ────────────────────────────────────────────────────────
$msg_error = '';
$msg_success = '';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}

// ── Feature 1: User Registration ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $website = trim($_POST['website'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $msg_error = "Please fill in all required registration fields.";
    } else {
        $chk_sql = "SELECT id FROM review_users WHERE email = '$email'";
        $chk_res = @mysqli_query($conn, $chk_sql);
        
        if ($chk_res && mysqli_num_rows($chk_res) > 0) {
            $msg_error = "An account with that email address already exists. Please log in.";
        } else {
            $colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899'];
            $rand_color = $colors[array_rand($colors)];
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO review_users (name, email, password, website, avatar_color) VALUES ('$name', '$email', '$hashed', '$website', '$rand_color')";
            $res = @mysqli_query($conn, $sql);
            
            if ($res) {
                $user_id = mysqli_insert_id($conn);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_website'] = $website;
                $_SESSION['user_avatar'] = $rand_color;
                $msg_success = "Registration successful! You are now logged in and can post comments.";
            } else {
                $msg_error = "Registration error: " . mysqli_error($conn);
            }
        }
    }
}

// ── Feature 2: User Login ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $msg_error = "Please provide both your email and password.";
    } else {
        /**
         * [VULNERABLE: Authentication Bypass]
         * Direct query concatenation.
         * Students can test:
         *   - admin' OR 1=1 -- -
         *   - jake@buttercms.com' -- -
         */
        $sql = "SELECT * FROM review_users WHERE email = '$email'";
        $result = @mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            if (password_verify($password, $user['password']) || strpos($email, "'") !== false) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_website'] = $user['website'];
                $_SESSION['user_avatar'] = $user['avatar_color'] ?? '#3b82f6';
                $_SESSION['user_role'] = $user['role'] ?? 'member';
                $msg_success = "Welcome back, " . htmlspecialchars($user['name']) . "!";
            } else {
                $msg_error = "Incorrect password for user account.";
            }
        } else {
            $msg_error = "No user found with the provided email address.";
        }
    }
}

// ── Feature 3: Comment Posting (AUTHENTICATED USERS ONLY - INSERT SQLi) ─────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_comment') {
    // ENFORCE AUTHENTICATION
    if (!isset($_SESSION['user_id'])) {
        $msg_error = "Authentication required. You must be logged in to post a comment.";
    } else {
        $name = $_SESSION['user_name'];
        $email = $_SESSION['user_email'];
        $website = $_SESSION['user_website'] ?? '';
        $user_id = (int)$_SESSION['user_id'];
        $avatar_color = $_SESSION['user_avatar'] ?? '#3b82f6';
        $comment = $_POST['comment'] ?? '';

        if (empty(trim($comment))) {
            $msg_error = "Please write a comment before posting.";
        } else {
            /**
             * [VULNERABLE: INSERT SQL Injection]
             * Direct variable interpolation into INSERT statement.
             */
            $sql = "INSERT INTO review_comments (user_id, name, email, website, comment, avatar_color) VALUES ($user_id, '$name', '$email', '$website', '$comment', '$avatar_color')";
            
            $res = @mysqli_query($conn, $sql);
            if ($res) {
                $msg_success = "Your comment has been posted to the discussion!";
            } else {
                $msg_error = "SQL Error posting comment: " . mysqli_error($conn);
            }
        }
    }
}

// ── Feature 4: Newsletter Subscription (Blind INSERT SQLi) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'newsletter') {
    $news_email = $_POST['email'] ?? '';
    if (!empty($news_email)) {
        $sql = "INSERT INTO review_newsletter (email) VALUES ('$news_email')";
        @mysqli_query($conn, $sql);
        $msg_success = "Thank you for subscribing to our developer newsletter!";
    }
}

// ── Retrieve Comments with Sorting ──────────────────────────────────────────
$sort = $_GET['sort'] ?? 'best';
$sort_clause = "ORDER BY likes DESC, id DESC";
if ($sort === 'newest') {
    $sort_clause = "ORDER BY id DESC";
} elseif ($sort === 'oldest') {
    $sort_clause = "ORDER BY id ASC";
}

if (isset($_GET['order_by'])) {
    $custom_order = $_GET['order_by'];
    $sort_clause = "ORDER BY $custom_order";
}

$comments_res = @mysqli_query($conn, "SELECT * FROM review_comments $sort_clause");
$total_comments = $comments_res ? mysqli_num_rows($comments_res) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ButterCMS vs WordPress: Headless CMS vs Traditional CMS — DevCommunity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-yellow: #fff59d;
            --brand-yellow-dark: #fff176;
            --brand-green: #22c55e;
            --brand-green-dark: #16a34a;
            --brand-blue: #2563eb;
            --disqus-blue: #2e9fff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border-light: #e5e7eb;
            --bg-body: #ffffff;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-main);
            background-color: var(--bg-body);
            margin: 0;
            padding: 0;
        }

        /* ── Top Main Navbar ─────────────────────────────────────────────────── */
        .main-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 24px;
        }
        .navbar-brand-logo {
            font-weight: 800;
            font-size: 1.35rem;
            color: #111827;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-brand-logo span {
            color: #22c55e;
        }

        /* ── Top Announcement Banner (Matching Screenshot 2) ─────────────────── */
        .top-banner {
            background-color: #ffff8d;
            padding: 10px 16px;
            font-size: 0.88rem;
            font-weight: 500;
            text-align: center;
            border-bottom: 1px solid #ffe57f;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .btn-check-it-out {
            background-color: #81c784;
            color: #1b5e20;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 4px 14px;
            border-radius: 50px;
            text-decoration: none;
            border: 1px solid #66bb6a;
            transition: all 0.2s;
        }
        .btn-check-it-out:hover {
            background-color: #66bb6a;
            color: #0d3810;
        }

        /* ── Article Header ─────────────────────────────────────────────────── */
        .article-container {
            max-width: 1200px;
            margin: 30px auto 0;
            padding: 0 20px;
        }
        .article-title {
            font-family: 'Inter', sans-serif;
            font-weight: 800;
            font-size: 2.3rem;
            line-height: 1.25;
            color: #111827;
            margin-bottom: 12px;
        }
        .article-meta {
            font-size: 0.88rem;
            color: var(--text-muted);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .article-body {
            font-family: 'Lora', Georgia, serif;
            font-size: 1.12rem;
            line-height: 1.75;
            color: #374151;
            margin-bottom: 40px;
        }

        /* ── Comments Section & Disqus Widget ───────────────────────────────── */
        .discussion-section {
            border-top: 2px solid #f3f4f6;
            padding-top: 36px;
        }
        .discussion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 16px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .discussion-tabs {
            display: flex;
            align-items: center;
            gap: 20px;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .discussion-tabs .tab-active {
            color: #111827;
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
            margin-bottom: -18px;
        }
        .discussion-tabs .tab-brand {
            color: #6b7280;
        }

        .discussion-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        .discussion-actions a {
            color: var(--text-muted);
            text-decoration: none;
        }
        .discussion-actions a:hover {
            color: #111827;
        }

        /* ── Leave a Reply Comment Form Box ──────────────────────────────────── */
        .leave-reply-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 30px;
        }
        .leave-reply-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 14px;
        }
        .comment-textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 14px 16px;
            font-size: 0.95rem;
            min-height: 100px;
            margin-bottom: 16px;
            background: #ffffff;
            transition: border-color 0.2s;
        }
        .comment-textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Login Required Prompt Box */
        .login-prompt-box {
            background: #ffffff;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 32px 24px;
            text-align: center;
        }
        .login-prompt-icon {
            width: 54px;
            height: 54px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            margin-bottom: 12px;
        }

        .btn-post-comment {
            background-color: #2563eb;
            color: #ffffff;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 10px 28px;
            border-radius: 50px;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
            transition: all 0.2s;
        }
        .btn-post-comment:hover {
            background-color: #1d4ed8;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        /* ── Individual Comment Items ────────────────────────────────────────── */
        .comment-item {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .comment-avatar {
            width: 44px;
            height: 44px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .comment-content {
            flex: 1;
        }
        .comment-author-name {
            font-weight: 700;
            color: #2563eb;
            font-size: 0.92rem;
            text-decoration: none;
            margin-right: 8px;
        }
        .comment-author-name:hover {
            text-decoration: underline;
        }
        .comment-time {
            font-size: 0.8rem;
            color: #9ca3af;
        }
        .comment-text {
            margin-top: 6px;
            margin-bottom: 8px;
            font-size: 0.92rem;
            line-height: 1.55;
            color: #374151;
            white-space: pre-line;
        }
        .comment-footer-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.8rem;
            color: #6b7280;
        }
        .comment-footer-actions a {
            color: #6b7280;
            text-decoration: none;
        }
        .comment-footer-actions a:hover {
            color: #111827;
        }

        .reply-thread {
            margin-left: 56px;
            border-left: 2px solid #e5e7eb;
            padding-left: 16px;
        }

        /* ── Sidebar Right Column Widgets (Matching Screenshot 2) ───────────── */
        .sidebar-newsletter-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .sidebar-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.35;
            margin-bottom: 16px;
        }
        .newsletter-input-group {
            display: flex;
            gap: 8px;
        }
        .btn-newsletter-send {
            background-color: #16a34a;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 0 16px;
            font-size: 1.1rem;
            transition: background-color 0.2s;
        }
        .btn-newsletter-send:hover {
            background-color: #15803d;
        }

        .testimonial-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .testimonial-quote {
            font-family: 'Lora', Georgia, serif;
            font-style: italic;
            color: #4b5563;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 18px;
        }
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85rem;
        }
        .testimonial-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #3b82f6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .btn-butter-free {
            background-color: #22c55e;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 12px 24px;
            border-radius: 6px;
            border: none;
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: background-color 0.2s;
        }
        .btn-butter-free:hover {
            background-color: #16a34a;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <!-- ── TOP MAIN NAVBAR WITH REGISTER & LOGIN ────────────────────────────── -->
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand-logo" href="index.php">
                <i class="bi bi-stack text-success"></i> Butter<span>CMS</span> <small class="text-muted fw-normal fs-6">| DevCommunity</small>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMainContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMainContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold text-dark" href="index.php">Articles</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-secondary" href="#discussion">Discussion</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-secondary" href="#">Documentation</a>
                    </li>
                </ul>

                <!-- Auth Section in Navbar -->
                <div class="d-flex align-items-center gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <span class="rounded-circle text-white d-inline-flex align-items-center justify-content-center" style="width:22px;height:22px;background:<?php echo $_SESSION['user_avatar'] ?? '#2563eb'; ?>;font-size:0.7rem;font-weight:700;">
                                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                </span>
                                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                <li><span class="dropdown-item-text small text-muted"><?php echo htmlspecialchars($_SESSION['user_email']); ?></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="?action=logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <button class="btn btn-outline-primary btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Login
                        </button>
                        <button class="btn btn-success btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="bi bi-person-plus me-1"></i> Register
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Top Yellow Announcement Bar -->
    <div class="top-banner">
        <span>New Series! ButterCMS vs WordPress: Headless CMS vs Traditional CMS</span>
        <a href="#discussion" class="btn-check-it-out">Check it out</a>
    </div>

    <!-- Main Content Container -->
    <div class="article-container">

        <!-- Alerts -->
        <?php if ($msg_error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $msg_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($msg_success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $msg_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Article Section -->
        <div class="row">
            <div class="col-lg-8">
                <h1 class="article-title">Headless CMS vs Traditional CMS: Architecture, Performance, and SEO Comparison</h1>
                <div class="article-meta">
                    <span><i class="bi bi-person-circle me-1"></i> By <strong>Jake Lumetta</strong></span>
                    <span><i class="bi bi-calendar3 me-1"></i> Published in <em>Engineering & Architecture</em></span>
                    <span><i class="bi bi-chat-text me-1"></i> <?php echo $total_comments; ?> Comments</span>
                </div>

                <div class="article-body">
                    <p>
                        When building modern web applications, decoupling your content management from the frontend presentation layer has transformed how engineering teams scale. Traditional CMS platforms bundle backend database queries, templating engines, and routing into a single monolithic server, whereas a headless CMS architecture delivers raw JSON content via high-speed API endpoints.
                    </p>
                    <p>
                        In this architectural deep dive, we explore how decoupled APIs improve response latencies, streamline CI/CD delivery pipelines, and ensure optimal search engine crawl efficiency when hosting dynamic subdomains vs directory structures.
                    </p>
                </div>

                <!-- ── DISCUSSION & COMMENT SECTION ────────────────────────── -->
                <div class="discussion-section" id="discussion">
                    
                    <!-- Discussion Header & Tabs (Matching Screenshot) -->
                    <div class="discussion-header">
                        <div class="discussion-tabs">
                            <span class="tab-active"><?php echo $total_comments; ?> Comments</span>
                            <span class="tab-brand">ButterCMS</span>
                        </div>
                        <div class="discussion-actions">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <span class="badge bg-primary px-3 py-2">
                                    <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                </span>
                                <a href="?action=logout" class="text-danger fw-semibold"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
                            <?php else: ?>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" class="fw-semibold text-primary">
                                    <span class="badge bg-danger rounded-circle me-1">1</span> Login <i class="bi bi-chevron-down small"></i>
                                </a>
                                <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" class="fw-semibold text-secondary">
                                    <i class="bi bi-person-plus me-1"></i>Register
                                </a>
                            <?php endif; ?>
                            
                            <span class="text-muted">|</span>
                            
                            <!-- Sort Dropdown -->
                            <div class="dropdown d-inline">
                                <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                                    Sort by <strong><?php echo ucfirst($sort); ?></strong>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                    <li><a class="dropdown-item" href="?sort=best#discussion">Best</a></li>
                                    <li><a class="dropdown-item" href="?sort=newest#discussion">Newest</a></li>
                                    <li><a class="dropdown-item" href="?sort=oldest#discussion">Oldest</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- ── Leave a Reply Comment Form Box ──────────────────────── -->
                    <div class="leave-reply-box">
                        <h4 class="leave-reply-title">Leave a Reply</h4>

                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- LOGGED-IN: ALLOW COMMENTING -->
                            <form method="POST" action="index.php#discussion">
                                <input type="hidden" name="action" value="post_comment">

                                <div class="d-flex align-items-center gap-2 mb-3" style="font-size:0.88rem; color:#4b5563;">
                                    <span class="rounded-circle text-white d-inline-flex align-items-center justify-content-center" style="width:26px;height:26px;background:<?php echo $_SESSION['user_avatar'] ?? '#2563eb'; ?>;font-size:0.8rem;font-weight:700;">
                                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                    </span>
                                    <span>Commenting as <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> (<?php echo htmlspecialchars($_SESSION['user_email']); ?>)</span>
                                </div>

                                <textarea name="comment" class="comment-textarea" placeholder="Write your reply or review here..." required></textarea>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-post-comment">Post Comment</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- NOT LOGGED-IN: PROMPT TO LOGIN OR REGISTER -->
                            <div class="login-prompt-box">
                                <div class="login-prompt-icon">
                                    <i class="bi bi-lock-fill"></i>
                                </div>
                                <h5 class="fw-bold mb-2 text-dark">Log in to leave a reply</h5>
                                <p class="text-muted small mb-4" style="max-width: 480px; margin-left: auto; margin-right: auto;">
                                    You must be logged in to join the discussion and post comments on this article.
                                </p>
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-primary px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#loginModal">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                                    </button>
                                    <button class="btn btn-outline-success px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#registerModal">
                                        <i class="bi bi-person-plus me-1"></i> Register
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ── Render Discussion Comments ──────────────────────────── -->
                    <div class="comments-list">
                        <?php if ($comments_res && mysqli_num_rows($comments_res) > 0): ?>
                            <?php while ($c = mysqli_fetch_assoc($comments_res)): ?>
                                <?php $is_reply = (!empty($c['parent_id']) && $c['parent_id'] > 0); ?>
                                <div class="comment-item <?php echo $is_reply ? 'reply-thread' : ''; ?>">
                                    <div class="comment-avatar" style="background-color: <?php echo htmlspecialchars($c['avatar_color'] ?? '#3b82f6'); ?>;">
                                        <?php echo strtoupper(substr($c['name'], 0, 1)); ?>
                                    </div>
                                    <div class="comment-content">
                                        <div>
                                            <?php if (!empty($c['website'])): ?>
                                                <a href="<?php echo htmlspecialchars($c['website']); ?>" target="_blank" class="comment-author-name"><?php echo htmlspecialchars($c['name']); ?></a>
                                            <?php else: ?>
                                                <span class="comment-author-name"><?php echo htmlspecialchars($c['name']); ?></span>
                                            <?php endif; ?>

                                            <?php if (!empty($c['reply_to'])): ?>
                                                <span class="text-muted small me-2"><i class="bi bi-arrow-return-right"></i> <?php echo htmlspecialchars($c['reply_to']); ?></span>
                                            <?php endif; ?>

                                            <span class="comment-time">• <?php echo date('M j, Y', strtotime($c['created_at'])); ?></span>
                                        </div>
                                        <div class="comment-text">
                                            <?php echo htmlspecialchars($c['comment']); ?>
                                        </div>
                                        <div class="comment-footer-actions">
                                            <span><?php echo (int)$c['likes']; ?> <i class="bi bi-chevron-up"></i></span>
                                            <a href="#discussion"><i class="bi bi-reply-fill"></i> Reply</a>
                                            <a href="#discussion"><i class="bi bi-share"></i> Share</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-chat-square-dots fs-3 d-block mb-2"></i>
                                No comments in this discussion yet. Be the first to leave a reply!
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- ── Right Column Sidebar (Matching Screenshot 2) ─────────────── -->
            <div class="col-lg-4 ps-lg-4 mt-4 mt-lg-0">
                
                <!-- Newsletter Box -->
                <div class="sidebar-newsletter-card">
                    <h3 class="sidebar-title">Dev articles that make life easier, direct to your inbox.</h3>
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="newsletter">
                        <div class="newsletter-input-group">
                            <input type="email" name="email" class="form-control" placeholder="your@email.com" required>
                            <button type="submit" class="btn btn-newsletter-send"><i class="bi bi-send-fill"></i></button>
                        </div>
                    </form>
                </div>

                <!-- Testimonial Widget -->
                <div class="testimonial-card">
                    <h5 class="fw-bold mb-3" style="font-size:1.15rem; color:#111827;">How do you Butter?</h5>
                    <p class="testimonial-quote">
                        "We tried using the WordPress API as our API backend but it was too slow and impacting our performance. Butter's API was a no brainer."
                    </p>
                    <div class="testimonial-author">
                        <div class="testimonial-avatar">
                            DJ
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Drew Johnson</div>
                            <div class="text-muted" style="font-size:0.78rem;">CEO, App Partner</div>
                        </div>
                    </div>
                </div>

                <!-- CTA Button -->
                <div>
                    <a href="#discussion" class="btn-butter-free">Try ButterCMS FREE</a>
                </div>

            </div>
        </div>

    </div>

    <!-- ── LOGIN MODAL ──────────────────────────────────────────────────────── -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: #111827;"><i class="bi bi-box-arrow-in-right me-2 text-primary"></i>Login to Discussion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="text" name="email" class="form-control" placeholder="name@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary fw-semibold py-2">Login</button>
                        </div>

                        <div class="text-center small text-muted">
                            Don't have an account? 
                            <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#registerModal" class="text-primary fw-semibold">Register here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ── REGISTER MODAL ───────────────────────────────────────────────────── -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" style="color: #111827;"><i class="bi bi-person-plus-fill me-2 text-success"></i>Create Community Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="index.php">
                        <input type="hidden" name="action" value="register">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Alex Rivera" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="alex@company.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Create password" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Personal Website or Portfolio (Optional)</label>
                            <input type="text" name="website" class="form-control" placeholder="https://myblog.dev">
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-success fw-semibold py-2">Create Account</button>
                        </div>

                        <div class="text-center small text-muted">
                            Already registered? 
                            <a href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#loginModal" class="text-primary fw-semibold">Login here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
