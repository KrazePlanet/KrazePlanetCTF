<?php
// ============================================================
// Database Configuration
// ============================================================
$dbname = 'VexiumCTF';
$username = 'root';
$password = '';
$hosts = ['127.0.0.1', 'localhost'];

// Table Names Configuration
$table_users = 'community_users';
$table_comments = 'community_comments';


$pdo = null;
$lastException = null;
foreach ($hosts as $h) {
    try {
        $pdo = new PDO("mysql:host=$h;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8 COLLATE utf8_general_ci");
        $pdo->exec("USE `$dbname`");
        break;
    } catch(PDOException $e) {
        $lastException = $e;
    }
}
if (!$pdo) {
    die("Connection failed: " . ($lastException ? $lastException->getMessage() : "Unknown error"));
}

// Initialize database tables
function initializeDatabase($pdo) {
    global $table_users, $table_comments;
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_users} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name TEXT,
            middle_name TEXT,
            last_name TEXT,
            full_name TEXT,
            phone TEXT,
            organization TEXT,
            country TEXT,
            user_category TEXT,
            bio TEXT,
            website TEXT,
            location TEXT,
            avatar TEXT,
            role ENUM('user', 'admin') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Upgrade existing table columns if needed to prevent length truncation errors
    try {
        $pdo->exec("
            ALTER TABLE {$table_users} 
            MODIFY COLUMN username VARCHAR(255) NOT NULL,
            MODIFY COLUMN email VARCHAR(255) NOT NULL,
            MODIFY COLUMN first_name TEXT,
            MODIFY COLUMN middle_name TEXT,
            MODIFY COLUMN last_name TEXT,
            MODIFY COLUMN full_name TEXT,
            MODIFY COLUMN phone VARCHAR(10),
            MODIFY COLUMN organization TEXT,
            MODIFY COLUMN country TEXT,
            MODIFY COLUMN user_category TEXT,
            MODIFY COLUMN bio TEXT,
            MODIFY COLUMN website TEXT,
            MODIFY COLUMN location TEXT,
            MODIFY COLUMN avatar TEXT
        ");
    } catch(Exception $ex) {
        // Table created fresh or columns already updated
    }

    // Comments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_comments} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Insert default admin user if none exists
    $check = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE role = 'admin'");
    $check->execute();
    if ($check->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, first_name, last_name, full_name, role) VALUES (?, ?, ?, 'System', 'Admin', 'System Administrator', 'admin')");
        $stmt->execute([
            'admin',
            'admin@community.local',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
    }
}
initializeDatabase($pdo);

// Session management
session_start();

// Authentication Helpers
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        die('ACCESS denied. Admin privileges required.');
    }
}

function getCurrentUser($pdo) {
    global $table_users;
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if (!$u) {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['role']);
        return null;
    }
    return $u;
}

function loginUser($pdo, $username, $password) {
    global $table_users;
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function logoutUser() {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    header("Location: index.php");
    exit();
}

// Logout action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
}

// Handle AJAX Captcha Reload
if (isset($_GET['action']) && $_GET['action'] === 'get_captcha') {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $_SESSION['captcha_code'] = '';
    for ($i = 0; $i < 6; $i++) {
        $_SESSION['captcha_code'] .= $chars[rand(0, strlen($chars) - 1)];
    }
    header('Content-Type: application/json');
    echo json_encode(['captcha' => $_SESSION['captcha_code']]);
    exit();
}

// Generate a fresh random Captcha Code on every GET request or if not set
if ($_SERVER['REQUEST_METHOD'] === 'GET' || !isset($_SESSION['captcha_code'])) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $_SESSION['captcha_code'] = '';
    for ($i = 0; $i < 6; $i++) {
        $_SESSION['captcha_code'] .= $chars[rand(0, strlen($chars) - 1)];
    }
}

// Register POST
$authError = ''; $authSuccess = '';
if (!isset($_SESSION['user_id']) && isset($_POST['register'])) {
    $u   = trim($_POST['reg_username']  ?? '');
    $e   = trim($_POST['reg_email']     ?? '');
    $p   =      $_POST['reg_password']  ?? '';
    $c   =      $_POST['reg_confirm']   ?? '';
    $fn  = trim($_POST['reg_first_name']  ?? '');
    $mn  = trim($_POST['reg_middle_name'] ?? '');
    $ln  = trim($_POST['reg_last_name']   ?? '');
    $ph  = substr(trim($_POST['reg_phone'] ?? ''), 0, 10);
    $org = trim($_POST['reg_organization']?? '');
    $ct  = trim($_POST['reg_country']     ?? '');
    $cat = trim($_POST['reg_category']    ?? '');
    $bio = trim($_POST['reg_bio']         ?? '');
    $cap = trim($_POST['captcha']         ?? '');

    $full_name = trim("$fn $mn $ln");
    if (empty($full_name)) {
        $full_name = $u;
    }

    if (empty($u) || empty($e) || empty($p)) {
        $authError = 'Username, Email, and Password are required.';
    } elseif ($p !== $c) {
        $authError = 'Passwords do not match.';
    } elseif (strlen($p) < 6) {
        $authError = 'Password must be at least 6 characters long.';
    } elseif (strtoupper($cap) !== $_SESSION['captcha_code']) {
        $authError = 'Incorrect Captcha code. Please try again.';
        // Regenerate captcha
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $_SESSION['captcha_code'] = '';
        for ($i = 0; $i < 6; $i++) {
            $_SESSION['captcha_code'] .= $chars[rand(0, strlen($chars) - 1)];
        }
    } else {
        try {
            $chk = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE username = ? OR email = ?");
            $chk->execute([$u, $e]);
            if ($chk->fetchColumn()) {
                $authError = 'Username or Email already exists.';
            } else {
                $ins = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, first_name, middle_name, last_name, full_name, phone, organization, country, user_category, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$u, $e, password_hash($p, PASSWORD_DEFAULT), $fn, $mn, $ln, $full_name, $ph, $org, $ct, $cat, $bio]);
                $authSuccess = 'Account successfully created! You can now login.';
                $_POST = [];
                // Refresh captcha code for security
                $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                $_SESSION['captcha_code'] = '';
                for ($i = 0; $i < 6; $i++) {
                    $_SESSION['captcha_code'] .= $chars[rand(0, strlen($chars) - 1)];
                }
            }
        } catch (PDOException $ex) {
            $authError = 'Registration failed: Unable to save user account. Please check your inputs.';
        }
    }
}

// Login POST
if (!isset($_SESSION['user_id']) && isset($_POST['login'])) {
    if (loginUser($pdo, $_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: index.php');
        exit();
    } else {
        $authError = 'Invalid Username or Password.';
    }
}

// Logged In Comments POST logic & State Init
$comments = [];
$user = null;
$error = '';
$success = '';

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

if (isset($_SESSION['user_id'])) {
    $user = getCurrentUser($pdo);
    if ($user && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
        $post_id = intval($_POST['post_id'] ?? 1);
        $content = trim($_POST['content'] ?? '');
        if (!empty($content)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO {$table_comments} (user_id, post_id, content) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], $post_id, $content]);
                $success = 'Comment posted successfully!';
            } catch (PDOException $e) {
                $error = 'Failed to post comment: ' . $e->getMessage();
            }
        }
    }
}

// Fetch database comments and merge dynamically
if (isset($_SESSION['user_id']) && $user) {
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
  <title>Community Hub - Global Portal</title>
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
    }

    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background-color: #ffffff;
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

    /* Form Container */
    .form-canvas {
      padding: 3rem 0 4rem 0;
    }

    .form-label-custom {
      font-weight: 600;
      font-size: 0.875rem;
      color: #1e293b;
      margin-bottom: 0.35rem;
    }

    .form-control-custom, .form-select-custom {
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      padding: 0.65rem 0.85rem;
      font-size: 0.95rem;
      color: #334155;
      background-color: #ffffff;
      transition: all 0.2s ease-in-out;
    }

    .form-control-custom:focus, .form-select-custom:focus {
      border-color: var(--accent-color);
      box-shadow: 0 0 0 0.2rem rgba(0, 96, 114, 0.15);
      outline: none;
    }

    .btn-portal-submit {
      background-color: var(--btn-primary);
      color: #ffffff;
      font-weight: 600;
      padding: 0.75rem 2rem;
      border-radius: 4px;
      border: none;
      transition: all 0.2s;
    }

    .btn-portal-submit:hover {
      background-color: var(--btn-primary-hover);
      color: #ffffff;
      transform: translateY(-1px);
    }

    /* Captcha Block */
    .captcha-display-box {
      background-color: #eef6f8;
      border: 1px solid #bce0e8;
      border-radius: 4px;
      padding: 0.5rem 1rem;
      font-size: 1.6rem;
      font-weight: 800;
      letter-spacing: 7px;
      color: var(--header-bg);
      text-align: center;
      user-select: none;
    }

    .captcha-reload-link {
      font-size: 0.85rem;
      color: #0284c7;
      text-decoration: none;
      font-weight: 600;
      display: inline-block;
      margin-bottom: 0.35rem;
    }

    .captcha-reload-link:hover {
      text-decoration: underline;
    }

    /* Radio Group Card */
    .radio-option-item {
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }
    .radio-option-item strong {
      color: #0f172a;
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

    .scroll-top-btn {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      background-color: #006072;
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      text-decoration: none;
      float: right;
    }

    .scroll-top-btn:hover {
      background-color: #004553;
      color: #ffffff;
    }

    /* Comment Cards */
    .comment-card {
      border: 1px solid #e2e8f0;
      border-left: 4px solid var(--header-bg);
      border-radius: 6px;
      padding: 1.25rem;
      margin-bottom: 1rem;
      background: #f8fafc;
    }

    /* Sidebar list card */
    .sidebar-list {
      background-color: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .sidebar-header {
      padding: 1rem;
      border-bottom: 1px solid #cbd5e1;
      font-weight: 700;
      font-size: 0.85rem;
      text-transform: uppercase;
      color: #64748b;
      letter-spacing: 0.5px;
      background-color: #f8fafc;
    }

    .search-container-box {
      padding: 0.85rem;
      border-bottom: 1px solid #cbd5e1;
      background-color: #f8fafc;
    }

    .search-input-box {
      border: 1px solid #cbd5e1;
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
      border-bottom: 1px solid #cbd5e1;
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
      border: 1px solid #cbd5e1;
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
      border: 1px solid #cbd5e1;
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
  </style>
</head>
<body>

  <!-- Navigation Bar -->
  <header class="portal-navbar">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between">
        <a class="brand-logo" href="index.php">
          <div class="brand-icon">C</div>
          <span>COMMUNITY HUB</span>
        </a>

        <ul class="nav portal-nav d-none d-md-flex align-items-center">
          <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#about">About Us</a></li>
          <li class="nav-item"><a class="nav-link" href="discussions/">Discussions</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#articles">Articles</a></li>
          <li class="nav-item"><a class="nav-link" href="index.php#policies">Policies</a></li>
          <?php if (!isset($_SESSION['user_id'])): ?>
            <li class="nav-item"><a class="nav-link" href="index.php?view=register">Register</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?view=login">Login</a></li>
          <?php else: ?>
            <li class="nav-item"><a class="nav-link <?php echo ($_GET['view'] ?? '') === 'profile' ? 'active' : ''; ?>" href="index.php?view=profile">My Profile</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php?action=logout">Logout</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="index.php#contact">Contact us</a></li>
        </ul>

        <div>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="index.php?view=profile" class="text-white me-3 fw-semibold text-decoration-none" title="View Profile">
              <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($user['username']); ?>
            </a>
            <a href="index.php?action=logout" class="btn-submit-pill"><i class="bi bi-box-arrow-right"></i> Logout</a>
          <?php else: ?>
            <a href="index.php?view=register" class="btn-submit-pill"><i class="bi bi-journal-check"></i> Join Community</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <?php if (!isset($_SESSION['user_id'])): ?>
    <?php $authView = $_GET['view'] ?? 'register'; ?>

    <!-- Hero Banner -->
    <section class="hero-banner">
      <div class="container">
        <h1 class="hero-title"><?php echo $authView === 'register' ? 'Register' : 'Account Login'; ?></h1>
        <p class="hero-sub"><?php echo $authView === 'register' ? 'Create your community account to post, discuss, and connect' : 'Access your account to engage with global discussions'; ?></p>
      </div>
    </section>

    <!-- Main Registration / Login Form -->
    <main class="form-canvas">
      <div class="container">
        <?php if ($authError): ?>
          <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($authError); ?></div>
        <?php endif; ?>
        <?php if ($authSuccess): ?>
          <div class="alert alert-success mb-4"><i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($authSuccess); ?></div>
        <?php endif; ?>

        <?php if ($authView === 'register'): ?>
          <form method="POST">
            <!-- Row 1: Name fields -->
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label-custom">First Name</label>
                <input type="text" class="form-control form-control-custom" name="reg_first_name" value="<?php echo htmlspecialchars($_POST['reg_first_name'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label-custom">Middle Name</label>
                <input type="text" class="form-control form-control-custom" name="reg_middle_name" value="<?php echo htmlspecialchars($_POST['reg_middle_name'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label-custom">Last Name</label>
                <input type="text" class="form-control form-control-custom" name="reg_last_name" value="<?php echo htmlspecialchars($_POST['reg_last_name'] ?? ''); ?>">
              </div>
            </div>

            <!-- Row 2: Contact / Credentials -->
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label-custom">Email Id *</label>
                <input type="email" class="form-control form-control-custom" name="reg_email" value="<?php echo htmlspecialchars($_POST['reg_email'] ?? ''); ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label-custom">Phone Number</label>
                <input type="text" class="form-control form-control-custom" name="reg_phone" maxlength="10" value="<?php echo htmlspecialchars($_POST['reg_phone'] ?? ''); ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label-custom">Username *</label>
                <input type="text" class="form-control form-control-custom" name="reg_username" value="<?php echo htmlspecialchars($_POST['reg_username'] ?? ''); ?>" required>
              </div>
            </div>

            <!-- Row 3: Passwords & Organization -->
            <div class="row g-3 mb-3">
              <div class="col-md-4">
                <label class="form-label-custom">Password *</label>
                <input type="password" class="form-control form-control-custom" name="reg_password" required>
              </div>
              <div class="col-md-4">
                <label class="form-label-custom">Confirm Password *</label>
                <input type="password" class="form-control form-control-custom" name="reg_confirm" required>
              </div>
              <div class="col-md-4">
                <label class="form-label-custom">Organization / Institution</label>
                <input type="text" class="form-control form-control-custom" name="reg_organization" value="<?php echo $_POST['reg_organization'] ?? ''; ?>">
              </div>
            </div>

            <!-- Row 4: Affiliation / Bio -->
            <div class="mb-3">
              <label class="form-label-custom">Affiliation / Bio</label>
              <textarea class="form-control form-control-custom" name="reg_bio" rows="3" placeholder="Describe your background or organization..."><?php echo htmlspecialchars($_POST['reg_bio'] ?? ''); ?></textarea>
            </div>

            <!-- Row 5: Dropdowns -->
            <div class="row g-3 mb-4">
              <div class="col-md-4">
                <label class="form-label-custom">User Category</label>
                <select class="form-select form-select-custom" name="reg_category">
                  <option value="">Select User Category</option>
                  <option value="Academic">Academic / Student</option>
                  <option value="Researcher">Professional Researcher</option>
                  <option value="Industry">Industry Expert</option>
                  <option value="General">General Public</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label-custom">Topic of Interest</label>
                <select class="form-select form-select-custom">
                  <option value="">Select Topic Name</option>
                  <option value="Technology">Technology & Engineering</option>
                  <option value="Science">Natural Sciences</option>
                  <option value="Medicine">Health & Medicine</option>
                  <option value="Social">Social Sciences</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label-custom">Country</label>
                <select class="form-select form-select-custom" name="reg_country">
                  <option value="">Select Country Name</option>
                  <option value="United States">United States</option>
                  <option value="United Kingdom">United Kingdom</option>
                  <option value="Canada">Canada</option>
                  <option value="India">India</option>
                  <option value="Australia">Australia</option>
                  <option value="Germany">Germany</option>
                </select>
              </div>
            </div>

            <!-- Confirmation Checkbox -->
            <div class="mb-4">
              <label class="form-label-custom d-block">Confirmation</label>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="confirmEmail" checked>
                <label class="form-check-label text-dark" for="confirmEmail">
                  Send me a confirmation email
                </label>
              </div>
            </div>

            <!-- Register As Radio Options -->
            <div class="mb-4">
              <label class="form-label-custom d-block">Register as:</label>
              <div class="radio-option-item">
                <input class="form-check-input me-2" type="radio" name="register_as" id="regReader" checked>
                <label for="regReader"><strong>Reader:</strong> Notified by email on publication of new discussions or articles.</label>
              </div>
              <div class="radio-option-item">
                <input class="form-check-input me-2" type="radio" name="register_as" id="regAuthor">
                <label for="regAuthor"><strong>Author:</strong> Able to submit topics and comments to the site.</label>
              </div>
              <div class="radio-option-item">
                <input class="form-check-input me-2" type="radio" name="register_as" id="regReviewer">
                <label for="regReviewer"><strong>Reviewer:</strong> Willing to conduct peer review of submissions to the community.</label>
              </div>
            </div>

            <!-- Row 9: Captcha Code & Submit -->
            <div class="row align-items-end g-3 mt-4">
              <div class="col-md-4">
                <label class="form-label-custom">Captcha Code</label>
                <input type="text" class="form-control form-control-custom" name="captcha" placeholder="Enter Captcha" required>
              </div>
              <div class="col-md-4">
                <a href="javascript:void(0);" onclick="reloadCaptcha()" class="captcha-reload-link"><i class="bi bi-arrow-repeat me-1"></i>Reload Captcha Code</a>
                <div>
                  <canvas id="captchaCanvas" width="180" height="42" style="background:#eef6f8; border:1px solid #bce0e8; border-radius:4px; cursor:pointer;" onclick="reloadCaptcha()" title="Click to refresh Captcha"></canvas>
                  <div id="captchaFallback" class="captcha-display-box d-none"><?php echo $_SESSION['captcha_code']; ?></div>
                </div>
              </div>
              <div class="col-md-4 text-md-end">
                <button type="submit" name="register" class="btn btn-portal-submit w-100 w-md-auto fs-6">
                  Register Me !
                </button>
              </div>
            </div>
          </form>

        <?php else: ?>
          <!-- Login View -->
          <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
              <div class="card border shadow-sm p-4">
                <h3 class="fw-bold mb-3" style="color:var(--header-bg);">Sign In to Account</h3>
                <form method="POST">
                  <div class="mb-3">
                    <label class="form-label-custom">Username or Email</label>
                    <input type="text" class="form-control form-control-custom" name="username" required>
                  </div>
                  <div class="mb-4">
                    <label class="form-label-custom">Password</label>
                    <input type="password" class="form-control form-control-custom" name="password" required>
                  </div>
                  <button type="submit" name="login" class="btn btn-portal-submit w-100 mb-3">
                    Sign In
                  </button>
                  <div class="text-center">
                    <a href="index.php?view=register" class="text-decoration-none fw-semibold" style="color:var(--header-bg);">Don't have an account? Register now</a>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </main>

  <?php else: ?>
    <?php $view = $_GET['view'] ?? 'feed'; ?>

    <?php if ($view === 'profile'): ?>
      <!-- User Profile Page -->
      <section class="hero-banner">
        <div class="container">
          <h1 class="hero-title">User Profile</h1>
          <p class="hero-sub">View your complete registered account information</p>
        </div>
      </section>

      <main class="form-canvas">
        <div class="container">
          <div class="row g-4">
            <!-- Left Card: Profile Summary -->
            <div class="col-lg-4">
              <div class="card border shadow-sm p-4 text-center">
                <div class="mb-3">
                  <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width:90px; height:90px; background-color:#e0f2fe; color:var(--header-bg); font-size:2.5rem; font-weight:700;">
                    <i class="bi bi-person-fill"></i>
                  </div>
                </div>
                <h4 class="fw-bold mb-1" style="color:var(--header-bg);">
                  <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                </h4>
                <p class="text-muted mb-2">@<?php echo htmlspecialchars($user['username']); ?></p>
                <div class="mb-3">
                  <span class="badge rounded-pill" style="background-color:var(--header-bg); font-size:0.85rem;">
                    <?php echo strtoupper(htmlspecialchars($user['role'])); ?>
                  </span>
                </div>
                
                <hr class="my-3">

                <div class="text-start small">
                  <div class="mb-2">
                    <strong class="text-dark"><i class="bi bi-envelope me-2"></i>Email:</strong><br>
                    <span class="text-muted ms-4"><?php echo htmlspecialchars($user['email']); ?></span>
                  </div>
                  <div class="mb-2">
                    <strong class="text-dark"><i class="bi bi-telephone me-2"></i>Phone:</strong><br>
                    <span class="text-muted ms-4"><?php echo htmlspecialchars($user['phone'] ?: 'Not Provided'); ?></span>
                  </div>
                  <div class="mb-2">
                    <strong class="text-dark"><i class="bi bi-calendar-event me-2"></i>Member Since:</strong><br>
                    <span class="text-muted ms-4"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                  </div>
                </div>

                <a href="index.php" class="btn btn-outline-secondary w-100 mt-4 fw-semibold">
                  <i class="bi bi-arrow-left me-1"></i> Back to Discussions
                </a>
              </div>
            </div>

            <!-- Right Card: Full Details Grid -->
            <div class="col-lg-8">
              <div class="card border shadow-sm p-4 mb-4">
                <h4 class="fw-bold mb-4 pb-2 border-bottom" style="color:var(--header-bg);">
                  <i class="bi bi-person-vcard me-2"></i>Registration Details
                </h4>

                <!-- Personal Info -->
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:0.8rem; letter-spacing:1px;">Personal Information</h6>
                <div class="row g-3 mb-4">
                  <div class="col-sm-4">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">First Name</small>
                      <strong class="text-dark"><?php echo htmlspecialchars($user['first_name'] ?: 'N/A'); ?></strong>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">Middle Name</small>
                      <strong class="text-dark"><?php echo htmlspecialchars($user['middle_name'] ?: 'N/A'); ?></strong>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">Last Name</small>
                      <strong class="text-dark"><?php echo htmlspecialchars($user['last_name'] ?: 'N/A'); ?></strong>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">Full Name</small>
                      <strong class="text-dark"><?php echo htmlspecialchars($user['full_name'] ?: 'N/A'); ?></strong>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">Username</small>
                      <strong class="text-dark"><?php echo htmlspecialchars($user['username']); ?></strong>
                    </div>
                  </div>
                </div>

                <!-- Contact & Organization -->
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:0.8rem; letter-spacing:1px;">Contact & Affiliation</h6>
                <div class="row g-3 mb-4">
                  <div class="col-sm-6">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">Email Address</small>
                      <strong class="text-dark"><?php echo htmlspecialchars($user['email']); ?></strong>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">Phone Number</small>
                      <strong class="text-dark"><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></strong>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">Organization / Institution</small>
                      <strong class="text-dark"><?php echo $user['organization'] ?: 'N/A'; ?></strong>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">User Category</small>
                      <strong class="text-dark"><?php echo htmlspecialchars($user['user_category'] ?: 'N/A'); ?></strong>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    <div class="p-3 bg-light rounded border">
                      <small class="text-muted d-block mb-1">Country</small>
                      <strong class="text-dark"><?php echo htmlspecialchars($user['country'] ?: 'N/A'); ?></strong>
                    </div>
                  </div>
                </div>

                <!-- Bio & Affiliation -->
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:0.8rem; letter-spacing:1px;">Affiliation / Bio</h6>
                <div class="p-3 bg-light rounded border mb-2">
                  <p class="text-dark mb-0"><?php echo nl2br(htmlspecialchars($user['bio'] ?: 'No bio or affiliation provided.')); ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>

    <?php else: ?>
      <!-- Logged-in Discussions Feed -->
      <section class="hero-banner">
        <div class="container">
          <h1 class="hero-title">Community Discussions</h1>
          <p class="hero-sub">Engage with members, share insights, and discuss global topics</p>
        </div>
      </section>

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
    <?php endif; ?>
  <?php endif; ?>

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
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#about">About Us</a></li>
            <li><a href="discussions/">Discussions</a></li>
            <li><a href="index.php#articles">Articles</a></li>
            <li><a href="index.php?view=register">Register</a></li>
            <li><a href="index.php#contact">Contact us</a></li>
          </ul>
        </div>

        <!-- Col 3: Categories / Journals -->
        <div class="col-lg-3 col-md-6">
          <h5 class="footer-heading">Topics</h5>
          <ul class="footer-links-list">
            <li><a href="#">Technology & Innovation</a></li>
            <li><a href="#">Science & Research</a></li>
            <li><a href="#">Healthcare & Medicine</a></li>
            <li><a href="#">Social & Environmental Policy</a></li>
            <li><a href="#">Artificial Intelligence</a></li>
          </ul>
        </div>

        <!-- Col 4: Follow Us & Mission -->
        <div class="col-lg-3 col-md-6">
          <h5 class="footer-heading">Follow Us</h5>
          <div class="d-flex gap-2 mb-3">
            <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
            <a href="#" class="social-btn"><i class="bi bi-twitter"></i></a>
            <a href="#" class="social-btn"><i class="bi bi-linkedin"></i></a>
          </div>
          <p class="small text-light">
            Community Hub is a global open platform bringing together scholars, professionals, and researchers to share insights, conduct peer reviews, and foster open discussions.
          </p>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom-bar clearfix">
        <span class="float-start">&copy; 2026 Community Hub. All rights reserved.</span>
        <a href="#" class="scroll-top-btn" title="Scroll to top"><i class="bi bi-arrow-up"></i></a>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let currentCaptcha = "<?php echo $_SESSION['captcha_code'] ?? ''; ?>";

    function drawCaptcha(code) {
      const canvas = document.getElementById('captchaCanvas');
      if (!canvas || !code) return;
      const ctx = canvas.getContext('2d');
      const width = canvas.width;
      const height = canvas.height;

      // Clear & Fill Background
      ctx.fillStyle = '#eef6f8';
      ctx.fillRect(0, 0, width, height);

      // Border
      ctx.strokeStyle = '#bce0e8';
      ctx.lineWidth = 1;
      ctx.strokeRect(0, 0, width, height);

      // Random background noise lines
      for (let i = 0; i < 6; i++) {
        ctx.strokeStyle = ['#007791', '#0284c7', '#64748b', '#004e5d', '#006072'][Math.floor(Math.random() * 5)];
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(Math.random() * width, Math.random() * height);
        ctx.lineTo(Math.random() * width, Math.random() * height);
        ctx.stroke();
      }

      // Random noise dots
      for (let i = 0; i < 25; i++) {
        ctx.fillStyle = '#0284c7';
        ctx.beginPath();
        ctx.arc(Math.random() * width, Math.random() * height, 1, 0, Math.PI * 2);
        ctx.fill();
      }

      // Draw distorted/rotated characters
      ctx.font = 'bold 22px "Inter", "Segoe UI", sans-serif';
      ctx.textBaseline = 'middle';

      for (let i = 0; i < code.length; i++) {
        ctx.save();
        const x = 20 + (i * 25);
        const y = height / 2 + (Math.random() * 4 - 2);
        const angle = (Math.random() * 0.35) - 0.175; // rotation

        ctx.translate(x, y);
        ctx.rotate(angle);
        ctx.fillStyle = ['#004e5d', '#002832', '#006072', '#0f172a'][i % 4];
        ctx.fillText(code[i], 0, 0);
        ctx.restore();
      }
    }

    function reloadCaptcha() {
      fetch('index.php?action=get_captcha')
        .then(res => res.json())
        .then(data => {
          currentCaptcha = data.captcha;
          drawCaptcha(currentCaptcha);
          const fallback = document.getElementById('captchaFallback');
          if (fallback) fallback.innerText = currentCaptcha;
        })
        .catch(() => {
          window.location.reload();
        });
    }

    // Discussions Client-side Controllers
    const discussionsData = <?php echo isset($discussions) ? json_encode($discussions) : '[]'; ?>;
    let activePostId = 1;

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

    function toggleReplyBox(commentId) {
      const box = document.getElementById(`replyBox-${commentId}`);
      if (box) {
        box.style.display = box.style.display === 'block' ? 'none' : 'block';
      }
    }

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

    function loadPost(postId, element) {
      document.querySelectorAll('.post-list-item').forEach(item => item.classList.remove('active'));
      element.classList.add('active');

      activePostId = postId;
      const post = discussionsData.find(p => p.id === postId);
      if (post) {
        renderActivePost(post);
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      if (currentCaptcha) {
        drawCaptcha(currentCaptcha);
      }
      // Automatically refresh Captcha every 10 seconds (10000ms)
      setInterval(reloadCaptcha, 10000);

      // Load active discussions
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
