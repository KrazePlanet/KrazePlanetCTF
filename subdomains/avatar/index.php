<?php
// Lab 1623 — Unrestricted File Upload: PHP Profile Picture Leads to RCE
// Real-world finding: MTN Group career site allowed .php file upload as profile picture
// with full path disclosure in HTML source, enabling direct remote code execution.
// Reference: https://hackerone.com/reports/1164452
// Vendor: MTN Group | Weakness: Improper Input Validation | Severity: Critical (9-10)

session_start();

// ── Database ──────────────────────────────────────────────────────────────────
$db = new mysqli('localhost', 'root', '', 'KrazePlanet_DB');
if ($db->connect_error) { die('Database connection failed.'); }

// ── Table ─────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1623_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT '',
    password VARCHAR(255) NOT NULL,
    bio TEXT DEFAULT '',
    avatar_path VARCHAR(255) DEFAULT 'assets/default-avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die($db->error);

// ── Seed admin user (idempotent) ──────────────────────────────────────────────
$check = $db->query("SELECT COUNT(*) AS c FROM lab1623_users");
if ($check && $check->fetch_assoc()['c'] == 0) {
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $db->query("INSERT INTO lab1623_users (id, name, email, phone, password, bio, avatar_path) VALUES
        (1, 'Dr. Jean-Paul Kone', 'admin@mtn.cm', '+237 670000001', '$adminPass',
         'HR Director at MTN Cameroon. Managing recruitment and talent acquisition.',
         'uploads/1623/payload.php')
    ") or die($db->error);

    $userPass = password_hash('candidate123', PASSWORD_BCRYPT);
    $db->query("INSERT INTO lab1623_users (id, name, email, phone, password, bio, avatar_path) VALUES
        (2, 'Alice Ngo Mbarga', 'alice@email.com', '+237 690000002', '$userPass',
         'Software engineer passionate about mobile technologies.', '')
    ") or die($db->error);
}

// ── Helper ────────────────────────────────────────────────────────────────────
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Routing ───────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$isLogout = isset($_GET['logout']);
$isRegister = ($action === 'register');
$isUpload = ($action === 'upload');
$isCmd = ($action === 'cmd');
$error = '';
$success = '';

// ── Handle Logout ─────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

// ── Handle Registration ───────────────────────────────────────────────────────
if ($isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name  || !$email  || !$pass) {
        $error = 'Name, email and password are required.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $st = $db->prepare("INSERT INTO lab1623_users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $st->bind_param('ssss', $name, $email, $phone, $hash);
        if ($st->execute()) {
            $uid = $st->insert_id;
            $st->close();
            $_SESSION['lab1623_uid'] = $uid;
            $_SESSION['lab1623_name'] = $name;
            header('Location: /index.php?action=profile');
            exit;
        } else {
            $error = 'Email already registered. Try logging in.';
        }
        $st->close();
    }
}

// ── Handle Login ──────────────────────────────────────────────────────────────
if (!$isRegister && $_SERVER['REQUEST_METHOD'] === 'POST' && !$isUpload && !$isCmd) {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $st = $db->prepare("SELECT * FROM lab1623_users WHERE email = ?");
    $st->bind_param('s', $email);
    $st->execute();
    $res = $st->get_result();
    $user = $res->fetch_assoc();
    $st->close();

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['lab1623_uid'] = (int)$user['id'];
        $_SESSION['lab1623_name'] = $user['name'];
        header('Location: /index.php?action=profile');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}

// ── Handle File Upload ────────────────────────────────────────────────────────
if ($isUpload && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['lab1623_uid'])) {
    $uid = (int)$_SESSION['lab1623_uid'];

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $error = 'No file uploaded or upload error occurred.';
    } else {
        $file = $_FILES['avatar'];
        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $size = $file['size'];

        if ($size > 5 * 1024 * 1024) {
            $error = 'File too large (max 5MB).';
        } else {
            // ⚠️ VULNERABILITY: No file type validation!
            // Any extension (including .php) is accepted.
            $newName = 'avatar-' . $uid . '-' . date('d-m-Y-H-i-s') . '.' . $ext;
            $dest = 'uploads/1623/' . $newName;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $st = $db->prepare("UPDATE lab1623_users SET avatar_path = ? WHERE id = ?");
                $st->bind_param('si', $dest, $uid);
                $st->execute();
                $st->close();
                $success = 'Profile picture updated successfully.';
            } else {
                $error = 'Failed to move uploaded file. Check directory permissions.';
            }
        }
    }
}

// ── Handle Command Execution (via uploaded shell) ────────────────────────────
$cmd_result = '';
if ($isCmd && !empty($_SESSION['lab1623_uid'])) {
    $cmd = $_POST['cmd'] ?? '';
    if ($cmd) {
        $safe_cmd = escapeshellcmd($cmd);
        $raw = shell_exec($safe_cmd . ' 2>&1');
        $cmd_result = $raw !== null ? $raw : 'No output.';
    }
}

// ── Get current user ─────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab1623_uid'])) {
    $st = $db->prepare("SELECT * FROM lab1623_users WHERE id = ?");
    $st->bind_param('i', $_SESSION['lab1623_uid']);
    $st->execute();
    $res = $st->get_result();
    $currentUser = $res->fetch_assoc();
    $st->close();
}

// ── Redirect logged-in user away from login page ────────────────────────────
if ($currentUser && !$action) {
    header('Location: /index.php?action=profile');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MTN Careers — Application Portal</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
:root {
    --mtn-yellow: #FFCC00;
    --mtn-yellow-dark: #E6B800;
    --mtn-blue: #003580;
    --mtn-blue-light: #0050B3;
    --mtn-dark: #1A1A2E;
    --mtn-bg: #F0F2F5;
    --card-bg: #FFFFFF;
    --text: #1A1A2E;
    --text-dim: #6B7280;
    --border: #E5E7EB;
    --green: #10B981;
    --red: #EF4444;
    --radius: 10px;
}
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, sans-serif;
    background: var(--mtn-bg);
    color: var(--text);
    min-height: 100vh;
}
a { color: var(--mtn-blue); text-decoration: none; }
a:hover { text-decoration: underline; }

/* ── Header ───────────────────────────────────────────────────────────────── */
.header {
    background: linear-gradient(135deg, var(--mtn-blue) 0%, #002050 100%);
    color: #fff;
    padding: 0 24px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    position: sticky;
    top: 0;
    z-index: 100;
}
.header-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 700;
}
.header-brand .logo-icon {
    width: 36px; height: 36px;
    background: var(--mtn-yellow);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 900; color: var(--mtn-blue);
}
.header-right {
    display: flex; align-items: center; gap: 16px;
    font-size: 13px;
}
.header-right a { color: rgba(255,255,255,0.85); }
.header-right a:hover { color: var(--mtn-yellow); text-decoration: none; }

/* ── Container ────────────────────────────────────────────────────────────── */
.container { max-width: 960px; margin: 0 auto; padding: 32px 20px; }

/* ── Cards ────────────────────────────────────────────────────────────────── */
.card {
    background: var(--card-bg);
    border-radius: var(--radius);
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    padding: 28px 32px;
    margin-bottom: 20px;
}
.card h2 {
    font-size: 22px;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}
.card h2 i { color: var(--mtn-yellow); }

/* ── Form fields ──────────────────────────────────────────────────────────── */
.form-group { margin-bottom: 16px; }
.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-dim);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.form-group input, .form-group textarea, .form-group select {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid var(--border);
    border-radius: 6px;
    font-size: 15px;
    transition: border-color 0.2s;
    background: #FAFBFC;
}
.form-group input:focus, .form-group textarea:focus {
    outline: none;
    border-color: var(--mtn-blue);
    box-shadow: 0 0 0 3px rgba(0,53,128,0.1);
}

/* ── Buttons ──────────────────────────────────────────────────────────────── */
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-primary {
    background: var(--mtn-yellow);
    color: var(--mtn-blue);
}
.btn-primary:hover { background: var(--mtn-yellow-dark); }
.btn-outline {
    background: transparent;
    color: var(--mtn-blue);
    border: 1.5px solid var(--mtn-blue);
}
.btn-outline:hover { background: var(--mtn-blue); color: #fff; }
.btn-danger {
    background: var(--red);
    color: #fff;
}
.btn-danger:hover { background: #DC2626; }
.btn-sm { padding: 6px 14px; font-size: 13px; }

/* ── Messages ─────────────────────────────────────────────────────────────── */
.msg {
    padding: 12px 16px;
    border-radius: 6px;
    font-size: 14px;
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
}
.msg-error { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
.msg-success { background: #ECFDF5; color: #065F46; border: 1px solid #A7F3D0; }

/* ── Profile ──────────────────────────────────────────────────────────────── */
.profile-header {
    display: flex; align-items: center; gap: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 20px;
}
.avatar {
    width: 96px; height: 96px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--mtn-yellow);
    background: #E5E7EB;
}
.avatar-placeholder {
    width: 96px; height: 96px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--mtn-blue), var(--mtn-blue-light));
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; font-weight: 700;
    border: 3px solid var(--mtn-yellow);
}
.profile-info h1 { font-size: 24px; }
.profile-info .email { color: var(--text-dim); font-size: 14px; }
.profile-info .phone { color: var(--text-dim); font-size: 13px; margin-top: 2px; }
.upload-form {
    margin-top: 16px;
    padding: 16px;
    background: #F9FAFB;
    border: 2px dashed var(--border);
    border-radius: 8px;
}
.upload-form input[type="file"] {
    display: block;
    margin-bottom: 12px;
    font-size: 14px;
}
.upload-hint {
    font-size: 12px;
    color: var(--text-dim);
    margin-top: 6px;
}

/* ── Vulnerability Info ───────────────────────────────────────────────────── */
.vuln-banner {
    background: #FEF2F2;
    border: 1px solid #FECACA;
    border-left: 4px solid var(--red);
    padding: 16px 20px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
}
.vuln-banner strong { color: #991B1B; }
.vuln-banner code {
    background: #FEE2E2;
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 13px;
}

/* ── Uploaded file preview ────────────────────────────────────────────────── */
.uploaded-file {
    margin-top: 12px;
    padding: 10px 14px;
    background: #FFF7ED;
    border: 1px solid #FED7AA;
    border-radius: 6px;
    font-size: 13px;
}
.uploaded-file .path {
    font-family: monospace;
    background: #FFEDD5;
    padding: 2px 6px;
    border-radius: 3px;
    color: #9A3412;
}

/* ── Terminal / CMD Panel ─────────────────────────────────────────────────── */
.cmd-panel {
    margin-top: 20px;
    background: #1A1A2E;
    border-radius: 8px;
    overflow: hidden;
}
.cmd-header {
    background: #2D2D44;
    padding: 10px 16px;
    display: flex; align-items: center; gap: 8px;
    color: #A5B4FC;
    font-size: 13px;
    font-weight: 600;
}
.cmd-header i { color: var(--green); }
.cmd-body {
    padding: 16px;
}
.cmd-body input[type="text"] {
    width: 100%;
    padding: 8px 12px;
    background: #0D0D1A;
    border: 1px solid #3D3D5C;
    border-radius: 4px;
    color: #E0E7FF;
    font-family: monospace;
    font-size: 14px;
    margin-bottom: 8px;
}
.cmd-body input[type="text"]:focus { outline: none; border-color: #6366F1; }
.cmd-output {
    background: #0D0D1A;
    padding: 12px 16px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
    color: #A5F3FC;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 300px;
    overflow-y: auto;
    margin-top: 8px;
}

/* ── Source Code Hint ─────────────────────────────────────────────────────── */
.source-hint {
    background: #FEFCE8;
    border: 1px solid #FDE68A;
    padding: 14px 18px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 13px;
}
.source-hint code {
    background: #FEF9C3;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

/* ── Login / Register toggle ──────────────────────────────────────────────── */
.auth-toggle {
    text-align: center;
    margin-top: 16px;
    font-size: 14px;
    color: var(--text-dim);
}

/* ── Pre-planted shell alert ──────────────────────────────────────────────── */
.shell-alert {
    background: #EFF6FF;
    border: 1px solid #BFDBFE;
    border-left: 4px solid var(--mtn-blue);
    padding: 14px 18px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 14px;
}
.shell-alert strong { color: var(--mtn-blue); }
.shell-alert code {
    background: #DBEAFE;
    padding: 2px 6px;
    border-radius: 3px;
}

/* ── Responsive ───────────────────────────────────────────────────────────── */
@media (max-width: 640px) {
    .container { padding: 16px 12px; }
    .card { padding: 20px 16px; }
    .profile-header { flex-direction: column; text-align: center; }
    .header { padding: 0 12px; }
}
</style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- HEADER -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="header">
    <div class="header-brand">
        <div class="logo-icon">MTN</div>
        <span>MTN Careers</span>
    </div>
    <div class="header-right">
        <?php if ($currentUser): ?>
            <span><i class="fas fa-user"></i> <?= esc($currentUser['name']) ?></span>
            <a href="/index.php?action=profile"><i class="fas fa-id-card"></i> Profile</a>
            <a href="/index.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php else: ?>
            <a href="/index.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="/index.php?action=register"><i class="fas fa-user-plus"></i> Register</a>
        <?php endif; ?>
    </div>
</div>

<div class="container">

<?php if (!$currentUser): ?>
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- LOGIN PAGE -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <?php if (!$isRegister): ?>
        <div style="text-align:center;margin-bottom:24px;">
            <div style="display:inline-flex;align-items:center;gap:12px;background:var(--mtn-blue);color:#fff;padding:16px 32px;border-radius:10px;margin-bottom:8px;">
                <div style="background:var(--mtn-yellow);color:var(--mtn-blue);width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;">MTN</div>
                <div style="text-align:left;">
                    <div style="font-size:20px;font-weight:700;">Applicant Portal</div>
                    <div style="font-size:13px;opacity:0.8;">MTN Group — Cameroon</div>
                </div>
            </div>
        </div>
        <div class="card" style="max-width:420px;margin:0 auto;">
            <h2><i class="fas fa-sign-in-alt"></i> Sign In</h2>
            <?php if ($error): ?>
                <div class="msg msg-error"><i class="fas fa-exclamation-circle"></i> <?= esc($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['registered'])): ?>
                <div class="msg msg-success"><i class="fas fa-check-circle"></i> Registration successful! Please log in.</div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fas fa-arrow-right"></i> Sign In
                </button>
            </form>
            <div class="auth-toggle">
                Don't have an account? <a href="/index.php?action=register">Register here</a>
            </div>
        </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- REGISTER PAGE -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <?php else: ?>
        <div style="text-align:center;margin-bottom:24px;">
            <div style="display:inline-flex;align-items:center;gap:12px;background:var(--mtn-blue);color:#fff;padding:16px 32px;border-radius:10px;">
                <div style="background:var(--mtn-yellow);color:var(--mtn-blue);width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;">MTN</div>
                <div style="text-align:left;">
                    <div style="font-size:20px;font-weight:700;">Create Your Account</div>
                    <div style="font-size:13px;opacity:0.8;">Join MTN Group talent pool</div>
                </div>
            </div>
        </div>
        <div class="card" style="max-width:480px;margin:0 auto;">
            <h2><i class="fas fa-user-plus"></i> Register</h2>
            <?php if ($error): ?>
                <div class="msg msg-error"><i class="fas fa-exclamation-circle"></i> <?= esc($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="name" placeholder="e.g., John Doe" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone</label>
                    <input type="tel" name="phone" placeholder="+237 6XX XXX XXX">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" placeholder="Min. 6 characters" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-check-circle"></i> Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Repeat password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            <div class="auth-toggle">
                Already have an account? <a href="/index.php">Sign in</a>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PROFILE / DASHBOARD (LOGGED IN) -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->

    <!-- Profile Card -->
    <div class="card">
        <h2><i class="fas fa-id-card"></i> My Profile</h2>
        <?php if ($error): ?>
            <div class="msg msg-error"><i class="fas fa-exclamation-circle"></i> <?= esc($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="msg msg-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>

        <div class="profile-header">
            <?php
            $avatarSrc = $currentUser['avatar_path'] ? $currentUser['avatar_path'] : '';
            $initials = '';
            $words = explode(' ', $currentUser['name']);
            foreach ($words as $w) { $initials .= strtoupper($w[0] ?? ''); }
            $initials = substr($initials, 0, 2);
            ?>
            <?php if ($avatarSrc && file_exists($avatarSrc)): ?>
                <img class="avatar" src="<?= esc($avatarSrc) ?>" alt="Profile picture">
            <?php else: ?>
                <div class="avatar-placeholder"><?= esc($initials) ?></div>
            <?php endif; ?>
            <div class="profile-info">
                <h1><?= esc($currentUser['name']) ?></h1>
                <div class="email"><i class="fas fa-envelope"></i> <?= esc($currentUser['email']) ?></div>
                <?php if ($currentUser['phone']): ?>
                    <div class="phone"><i class="fas fa-phone"></i> <?= esc($currentUser['phone']) ?></div>
                <?php endif; ?>
                <?php if ($currentUser['bio']): ?>
                    <p style="margin-top:8px;font-size:14px;color:var(--text-dim);"><?= esc($currentUser['bio']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upload Form -->
        <?php if ($currentUser['id'] > 1): ?>
        <div class="upload-form">
            <form method="POST" action="/index.php?action=upload" enctype="multipart/form-data">
                <label style="font-weight:600;font-size:14px;display:block;margin-bottom:8px;">
                    <i class="fas fa-camera"></i> Upload Profile Picture
                </label>
                <input type="file" name="avatar" required>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-upload"></i> Upload
                </button>
                <div class="upload-hint">Accepted formats: JPG, PNG, GIF &mdash; max 5MB</div>
            </form>
        </div>
        <?php endif; ?>
    </div>





<?php endif; ?>
</div>


</body>
</html>
