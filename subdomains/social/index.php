<?php
// Lab 704 — IDOR: FriendZone Social Media — Private Photos, Messages & Profile Disclosure
// Platform: "FriendZone v1.0 Beta" — fictional social media / networking platform
// Vulnerability: Resources use UUIDs instead of sequential IDs, but there are NO ownership checks.
//   UUIDs are leaked through public galleries, friend suggestions, and activity feeds.
//   Any authenticated user can view ANY photo, message, or profile by its UUID.
// Real World: UUID/GUID is NOT authorization — many apps leak UUIDs and fail to verify ownership.
// Difficulty: Medium (Training) | UUID enumeration via publicly leaked values

session_start();

define('LAB_FLAG', 'flag{idor_friendzone_message_disclosure_704}');

// ── Database ──────────────────────────────────────────────────────────────────
$db_hosts = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];
$db = null;
foreach ($db_hosts as $h) {
    $db = @new mysqli($h, 'root', '');
    if (!$db->connect_error) {
        $db->query("CREATE DATABASE IF NOT EXISTS `KrazePlanet_DB` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->select_db('KrazePlanet_DB');
        break;
    }
}
if (!$db || $db->connect_error) { die('DB connection failed: ' . ($db ? $db->connect_error : 'Unable to connect to database')); }
if ($db->connect_error) { die('DB connection failed'); }

// ── Tables ────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab704_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_uuid VARCHAR(36) NOT NULL UNIQUE,
    bio VARCHAR(200),
    location VARCHAR(80),
    avatar_emoji VARCHAR(10) DEFAULT '😊',
    is_private TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die($db->error);

$db->query("CREATE TABLE IF NOT EXISTS lab704_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    photo_uuid VARCHAR(36) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    caption VARCHAR(200) NOT NULL,
    image_emoji VARCHAR(20) NOT NULL DEFAULT '🖼️',
    is_private TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES lab704_users(id)
)") or die($db->error);

$db->query("CREATE TABLE IF NOT EXISTS lab704_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    msg_uuid VARCHAR(36) NOT NULL UNIQUE,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES lab704_users(id),
    FOREIGN KEY (to_user_id) REFERENCES lab704_users(id)
)") or die($db->error);

// ── Seed data (idempotent) ────────────────────────────────────────────────────
function seed704($db) {
    $check = $db->query("SELECT COUNT(*) AS c FROM lab704_users");
    if ($check && $check->fetch_assoc()['c'] > 0) return;

    $p1 = password_hash('alice123', PASSWORD_BCRYPT);
    $p2 = password_hash('bob123', PASSWORD_BCRYPT);
    $p3 = password_hash('chloe123', PASSWORD_BCRYPT);

    $db->query("INSERT INTO lab704_users (id, name, email, password, user_uuid, bio, location, avatar_emoji, is_private) VALUES
        (1, 'Alice Johnson',  'alice@friendzone.com',  '$p1', 'f47ac10b-58cc-4372-a567-0e02b2c3d479', 'Digital artist & cat lover 🎨',    'San Francisco, CA', '🎨', 0),
        (2, 'Bob Martinez',   'bob@friendzone.com',    '$p2', '6ba7b810-9dad-41d4-80d4-00c04fd430c8', 'Code, coffee, repeat ☕',         'Austin, TX',         '☕', 0),
        (3, 'Chloe Williams', 'chloe@friendzone.com',  '$p3', '7c9d0e1f-2a3b-4c5d-8e6f-7091a2b3c4d5', 'Travel photographer ✈️',          'New York, NY',       '📸', 1)
    ") or die($db->error);

    $db->query("INSERT INTO lab704_photos (photo_uuid, user_id, caption, image_emoji, is_private) VALUES
        ('a1b2c3d4-e5f6-47a8-b9c0-123456789abc', 1, 'Sunset at Baker Beach last weekend 🌅', '🌅', 0),
        ('b2c3d4e5-f6a7-48b9-8c0d-23456789abcd', 1, 'My cat Mittens being adorable 🐱', '🐱', 0),
        ('c3d4e5f6-a7b8-49c0-a0d1-3456789abcde', 1, 'Sketchbook page — character design in progress 🎨', '🎨', 1),
        ('d4e5f6a7-b8c9-4a0d-81e2-456789abcdef', 2, 'New PC build! Custom water cooling 🖥️', '🖥️', 0),
        ('e5f6a7b8-c9d0-4b1e-82f3-56789abcdef0', 2, 'View from the office window this morning 🏙️', '🏙️', 0),
        ('f6a7b8c9-d0e1-4c2f-a304-6789abcdef01', 2, 'Team salary benchmarks — CONFIDENTIAL 📊', '📊', 1),
        ('a7b8c9d0-e1f2-4d30-8405-789abcdef012', 3, 'Times Square at midnight 🌃', '🌃', 0),
        ('b8c9d0e1-f2a3-4e41-8506-89abcdef0123', 3, 'Central Park in autumn colors 🍂', '🍂', 0),
        ('c9d0e1f2-a3b4-4f52-8607-9abcdef01234', 3, 'Confidential project notes — DO NOT SHARE 📝', '📝', 1)
    ") or die($db->error);

    $db->query("INSERT INTO lab704_messages (msg_uuid, from_user_id, to_user_id, subject, body, is_read) VALUES
        ('000001a1-b2c3-4d5e-8f67-891011121314', 1, 2, 'Your PC build is awesome!',
         'Hey Bob! Loved your new PC build post! That water cooling loop looks incredible. How long did it take you to set up?', 1),
        ('000002b2-c3d4-4e5f-9078-910111121314', 2, 1, 'Re: Your PC build is awesome!',
         'Thanks Alice! It took about 6 hours total — the custom loop was the trickiest part. Your cat pics are adorable btw! 🐱', 1),
        ('000003c3-d4e5-4f60-a189-101112131415', 1, 3, 'NYC photo spots?',
         'Hey Chloe! I saw you are a travel photographer. I am visiting NYC next month — any must-visit spots for great photos?', 0),
        ('000004d4-e5f6-4a71-b290-111213141516', 3, 1, 'Re: NYC photo spots?',
         'Hey Alice! Definitely hit up DUMBO at golden hour for the bridge shot. Also the High Line at sunset is gorgeous! Let me know if you want more recs. 📸', 1),
        ('000005e5-f6a7-4b82-8391-121314151617', 2, 3, 'Coffee meetup next week?',
         'Hey Chloe! I will be in NYC for a developer conference next Thursday. Would you be free to grab coffee and talk shop? ☕', 0),
        ('000006f6-a7b8-4c93-84a2-131415161718', 1, 2, 'Weekend hike this Saturday?',
         'Bob! A few of us are planning a hike at Mount Tam this Saturday. Weather looks perfect. You in? 🥾', 0),
        ('000007a7-b8c9-4d04-85b3-141516171819', 2, 1, 'Re: Weekend hike this Saturday?',
         'Sounds great! Count me in. What time and where should we meet? 🥾', 0),
        ('000008b8-c9d0-4e15-86c4-151617181920', 1, 3, 'Thanks for the tips!',
         'Those spots sound perfect! I will definitely check them out. If you are around, would love to say hi!', 0),
        ('000009c9-d0e1-4f26-87d5-161718192021', 3, 2, 'Re: Coffee meetup next week?',
         'Hey Bob! I would love to! There is a great cafe in Greenwich Village. Send me the details and we can coordinate! ☕', 1),
        ('000010d0-e1f2-4a37-88e6-171819202122', 3, 3, 'Security Audit — Action Required',
         'flag{idor_friendzone_message_disclosure_704} Dear Chloe, Our automated security scan has detected unusual access patterns on your account. Several private photos and messages may have been accessed from unrecognized IP addresses. Please review your recent activity and change your password immediately. This is a confidential security notification. — FriendZone Trust & Safety Team', 0)
    ") or die($db->error);
}
seed704($db);

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$action     = $_GET['action'] ?? '';
$isLogout   = isset($_GET['logout']);
$isRegister = ($action === 'register');
$isDash     = ($action === 'dashboard' || !$action);
$isExplr    = ($action === 'explore');
$isPhoto    = ($action === 'photo');
$isMsgs     = ($action === 'messages');
$isMsg      = ($action === 'message');
$isProfile  = ($action === 'profile');
$error      = '';

// ── Logout ────────────────────────────────────────────────────────────────────
if ($isLogout) {
    session_destroy();
    header('Location: /index.php');
    exit;
}

// ── POST: Register ────────────────────────────────────────────────────────────
if ($isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $bio   = trim($_POST['bio'] ?? '');
    $loc   = trim($_POST['location'] ?? '');
    if ($name && $email && strlen($pass) >= 4) {
        $h = password_hash($pass, PASSWORD_BCRYPT);
        // Generate a real RFC 4122 UUID v4
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set variant to RFC 4122
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        $avatars = ['🎨','☕','📸','🎮','🎵','🌮','🏀','🧗'];
        $avatar = $avatars[array_rand($avatars)];
        $st = $db->prepare('INSERT INTO lab704_users (name, email, password, user_uuid, bio, location, avatar_emoji) VALUES (?,?,?,?,?,?,?)');
        $st->bind_param('sssssss', $name, $email, $h, $uuid, $bio, $loc, $avatar);
        $st->execute();
        $st->close();
    }
    header('Location: /index.php');
    exit;
}

// ── POST: Login ───────────────────────────────────────────────────────────────
if (!$isRegister && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($email && $pass) {
        $st = $db->prepare('SELECT * FROM lab704_users WHERE email = ?');
        $st->bind_param('s', $email);
        $st->execute();
        $res = $st->get_result();
        $user = $res->fetch_assoc();
        $st->close();
        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['lab704_user'] = $user['id'];
            header('Location: /index.php?action=dashboard');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// ── Current user ──────────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab704_user'])) {
    $st = $db->prepare('SELECT * FROM lab704_users WHERE id = ?');
    $st->bind_param('i', $_SESSION['lab704_user']);
    $st->execute();
    $res = $st->get_result();
    $currentUser = $res->fetch_assoc();
    $st->close();
}

if ($currentUser && !$action && !$isLogout) {
    header('Location: /index.php?action=dashboard');
    exit;
}

// ═══════════════════════════════════════════════════════════════════════════════
//  VULNERABLE: No ownership checks on UUID-based resources.
//  UUIDs are leaked through public galleries, activity feeds, and friend
//  suggestions. Students discover that UUID ≠ authorization.
// ═══════════════════════════════════════════════════════════════════════════════

// ── VULNERABLE: Photo detail by UUID ──────────────────────────────────────────
$photoDetail = null;
if ($isPhoto && isset($_GET['uuid'])) {
    $uuid = $_GET['uuid'];
    $st = $db->prepare('SELECT p.*, u.name AS owner_name, u.avatar_emoji, u.user_uuid AS owner_uuid
                        FROM lab704_photos p JOIN lab704_users u ON p.user_id = u.id
                        WHERE p.photo_uuid = ?');
    $st->bind_param('s', $uuid);
    $st->execute();
    $res = $st->get_result();
    $photoDetail = $res->fetch_assoc();
    $st->close();
}

// ── VULNERABLE: Message detail by UUID ────────────────────────────────────────
$msgDetail = null;
if ($isMsg && isset($_GET['uuid'])) {
    $uuid = $_GET['uuid'];
    $st = $db->prepare('SELECT m.*,
                        fu.name AS from_name, fu.avatar_emoji AS from_emoji,
                        tu.name AS to_name, tu.avatar_emoji AS to_emoji
                        FROM lab704_messages m
                        JOIN lab704_users fu ON m.from_user_id = fu.id
                        JOIN lab704_users tu ON m.to_user_id = tu.id
                        WHERE m.msg_uuid = ?');
    $st->bind_param('s', $uuid);
    $st->execute();
    $res = $st->get_result();
    $msgDetail = $res->fetch_assoc();
    $st->close();
}

// ── VULNERABLE: Profile by UUID ───────────────────────────────────────────────
$profileUser = null;
if ($isProfile && isset($_GET['uuid'])) {
    $uuid = $_GET['uuid'];
    $st = $db->prepare('SELECT * FROM lab704_users WHERE user_uuid = ?');
    $st->bind_param('s', $uuid);
    $st->execute();
    $res = $st->get_result();
    $profileUser = $res->fetch_assoc();
    $st->close();
}

// ── Fetch logged-in user's data ──────────────────────────────────────────────
$myMessages = [];
$myPhotos = [];
$friendSuggestions = [];
if ($currentUser) {
    $st = $db->prepare('SELECT m.*,
                        fu.name AS from_name, fu.avatar_emoji AS from_emoji,
                        tu.name AS to_name, tu.avatar_emoji AS to_emoji
                        FROM lab704_messages m
                        JOIN lab704_users fu ON m.from_user_id = fu.id
                        JOIN lab704_users tu ON m.to_user_id = tu.id
                        WHERE m.from_user_id = ? OR m.to_user_id = ?
                        ORDER BY m.created_at DESC');
    $st->bind_param('ii', $currentUser['id'], $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $myMessages[] = $row;
    $st->close();

    $st = $db->prepare('SELECT * FROM lab704_photos WHERE user_id = ? ORDER BY created_at DESC');
    $st->bind_param('i', $currentUser['id']);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) $myPhotos[] = $row;
    $st->close();

    // Friend suggestions leak other users' UUIDs!
    $res = $db->query("SELECT id, name, user_uuid, bio, avatar_emoji, location FROM lab704_users WHERE id != {$currentUser['id']} ORDER BY RAND() LIMIT 4");
    if ($res) { while ($row = $res->fetch_assoc()) $friendSuggestions[] = $row; }
}

// ── Public gallery (shows all public photos with UUIDs) ──────────────────────
$publicPhotos = [];
$res = $db->query("SELECT p.*, u.name AS owner_name, u.avatar_emoji
                   FROM lab704_photos p JOIN lab704_users u ON p.user_id = u.id
                   WHERE p.is_private = 0
                   ORDER BY p.created_at DESC");
if ($res) { while ($row = $res->fetch_assoc()) $publicPhotos[] = $row; }

// ── Activity feed (leaks UUIDs of recent public uploads) ──────────────────────
$activityFeed = [];
$res = $db->query("SELECT p.*, u.name AS owner_name, u.avatar_emoji, u.user_uuid AS owner_uuid
                   FROM lab704_photos p JOIN lab704_users u ON p.user_id = u.id
                   WHERE p.is_private = 0
                   ORDER BY p.created_at DESC LIMIT 6");
if ($res) { while ($row = $res->fetch_assoc()) $activityFeed[] = $row; }

// ── Unread count ──────────────────────────────────────────────────────────────
$unreadCount = 0;
if ($currentUser) {
    $r = $db->query("SELECT COUNT(*) AS c FROM lab704_messages WHERE to_user_id = {$currentUser['id']} AND is_read = 0");
    if ($r) $unreadCount = $r->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FriendZone — Connect & Share</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:#F5F0FF;color:#1E1B2E;min-height:100vh}
a{color:#7C3AED;text-decoration:none}a:hover{text-decoration:underline}

/* ── Auth ─────────────────────────────────────────────────── */
.auth-wrap{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#2E1065 0%,#7C3AED 50%,#EC4899 100%);padding:24px}
.auth-card{width:430px;background:#fff;border-radius:24px;padding:44px;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.auth-logo{text-align:center;margin-bottom:28px}
.auth-logo .brand-icon{font-size:3rem;margin-bottom:6px}
.auth-title{font-size:1.5rem;font-weight:800;background:linear-gradient(135deg,#7C3AED,#EC4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.auth-sub{font-size:.85rem;color:#6B7280;margin-top:6px}
.auth-input{width:100%;padding:12px 16px;border:1px solid #E5E7EB;border-radius:12px;font-size:.9rem;margin-bottom:14px;color:#1F2937;background:#F9FAFB;transition:.15s}
.auth-input:focus{outline:none;border-color:#7C3AED;box-shadow:0 0 0 3px rgba(124,58,237,.12);background:#fff}
.auth-btn{width:100%;padding:13px;background:linear-gradient(135deg,#7C3AED,#EC4899);color:#fff;border:none;border-radius:12px;font-weight:700;font-size:.95rem;cursor:pointer;transition:.15s}
.auth-btn:hover{transform:translateY(-1px);box-shadow:0 4px 20px rgba(124,58,237,.3)}
.auth-switch{text-align:center;margin-top:18px;font-size:.85rem;color:#6B7280}
.error-msg{background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px}
.demo-box{margin-top:20px;padding:16px;background:#F5F3FF;border:1px solid #EDE9FE;border-radius:12px;font-size:.78rem;color:#6D28D9}
.demo-box strong{color:#5B21B6}

/* ── Layout ──────────────────────────────────────────────── */
.app{display:flex;min-height:100vh}
.sidebar{width:240px;background:#1E1B2E;padding:0;flex-shrink:0;display:flex;flex-direction:column}
.sidebar-brand{padding:24px 20px 20px;font-weight:800;font-size:1.15rem;display:flex;align-items:center;gap:10px;border-bottom:1px solid #2D2A42}
.sidebar-brand .brand-icon{font-size:1.5rem}
.sidebar-brand .brand-text{background:linear-gradient(135deg,#A78BFA,#F472B6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.sidebar-nav{padding:12px 0;flex:1}
.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:12px 20px;color:#9CA3AF;font-size:.88rem;font-weight:500;transition:.15s;border-left:3px solid transparent}
.sidebar-nav a:hover{color:#E5E7EB;background:rgba(255,255,255,.05);text-decoration:none}
.sidebar-nav a.active{color:#fff;background:rgba(124,58,237,.15);border-left-color:#7C3AED}
.sidebar-nav a svg{width:20px;height:20px;flex-shrink:0;stroke:#9CA3AF;fill:none;stroke-width:2}
.sidebar-nav a.active svg{stroke:#A78BFA}
.sidebar-nav a .badge-count{background:#7C3AED;color:#fff;font-size:.65rem;font-weight:700;padding:1px 7px;border-radius:10px;margin-left:auto}
.sidebar-footer{padding:20px;border-top:1px solid #2D2A42;font-size:.7rem;color:#4B4A6B}
.main{flex:1;padding:24px 32px;overflow:auto}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.topbar h1{font-size:1.3rem;font-weight:700;color:#1E1B2E}
.user-pill{background:#fff;border:1px solid #E5E7EB;padding:8px 18px 8px 12px;border-radius:40px;font-size:.82rem;font-weight:500;color:#4B5563;display:flex;align-items:center;gap:8px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.user-avatar{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;background:#F5F3FF}

/* ── Cards & Grid ─────────────────────────────────────────── */
.card{background:#fff;border:1px solid #E5E7EB;border-radius:16px;padding:24px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.card-title{font-size:.92rem;font-weight:700;color:#1E1B2E;margin-bottom:16px;display:flex;align-items:center;gap:8px}
.card-title .card-icon{font-size:1.2rem}

.feed-grid{display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start}
@media(max-width:900px){.feed-grid{grid-template-columns:1fr}}

/* ── Stats ───────────────────────────────────────────────── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px;margin-bottom:24px}
.stat-card{background:linear-gradient(135deg,#F5F3FF,#EDE9FE);border:1px solid #DDD6FE;border-radius:14px;padding:18px;text-align:center}
.stat-emoji{font-size:1.5rem;margin-bottom:6px}
.stat-value{font-size:1.3rem;font-weight:800;color:#4C1D95}
.stat-label{font-size:.72rem;color:#6D28D9;font-weight:600;text-transform:uppercase;letter-spacing:.3px}

/* ── Photo cards ──────────────────────────────────────────── */
.photo-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px}
.photo-card{background:#fff;border:1px solid #E5E7EB;border-radius:14px;overflow:hidden;transition:.15s}
.photo-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.06);transform:translateY(-2px)}
.photo-card .photo-emoji{display:flex;align-items:center;justify-content:center;height:120px;font-size:3rem;background:linear-gradient(135deg,#F5F3FF,#FCE7F3)}
.photo-card .photo-body{padding:14px}
.photo-card .photo-caption{font-size:.82rem;font-weight:600;color:#1E1B2E;margin-bottom:6px;line-height:1.3}
.photo-card .photo-meta{font-size:.72rem;color:#6B7280;display:flex;justify-content:space-between;align-items:center}
.photo-card .photo-owner{display:flex;align-items:center;gap:4px}
.photo-priv-badge{font-size:.6rem;background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:6px;font-weight:600}
.photo-uuid-display{font-size:.6rem;color:#9CA3AF;font-family:monospace;margin-top:4px}

/* ── Activity feed ────────────────────────────────────────── */
.activity-item{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #F3F4F6}
.activity-item:last-child{border-bottom:none}
.activity-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;background:#F5F3FF;flex-shrink:0}
.activity-content{flex:1;font-size:.82rem;color:#374151;line-height:1.4}
.activity-content strong{color:#1E1B2E}
.activity-content .activity-uuid{font-size:.65rem;color:#9CA3AF;font-family:monospace;display:block;margin-top:2px}
.activity-time{font-size:.68rem;color:#9CA3AF;white-space:nowrap}

/* ── People sidebar ───────────────────────────────────────── */
.suggest-card{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #F3F4F6}
.suggest-card:last-child{border-bottom:none}
.suggest-avatar{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;background:#F5F3FF;flex-shrink:0}
.suggest-info{flex:1}
.suggest-name{font-size:.82rem;font-weight:600;color:#1E1B2E}
.suggest-bio{font-size:.7rem;color:#6B7280;line-height:1.2;margin-top:1px}
.suggest-uuid{font-size:.6rem;color:#9CA3AF;font-family:monospace}

/* ── Message list ─────────────────────────────────────────── */
.msg-item{display:flex;gap:12px;padding:14px 0;border-bottom:1px solid #F3F4F6;cursor:pointer;transition:.15s}
.msg-item:hover{background:#FAFAFA;margin:0 -16px;padding:14px 16px;border-radius:8px}
.msg-item:last-child{border-bottom:none}
.msg-avatar{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;background:#F5F3FF;flex-shrink:0;margin-top:2px}
.msg-body{flex:1;min-width:0}
.msg-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:3px}
.msg-from{font-size:.85rem;font-weight:600;color:#1E1B2E}
.msg-date{font-size:.7rem;color:#9CA3AF}
.msg-subject{font-size:.83rem;color:#374151;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-preview{font-size:.78rem;color:#6B7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg-uuid-tag{font-size:.6rem;color:#9CA3AF;font-family:monospace;margin-top:2px}

/* ── Profile card ─────────────────────────────────────────── */
.profile-header{display:flex;align-items:center;gap:20px;margin-bottom:20px}
.profile-avatar-lg{width:72px;height:72px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.2rem;background:linear-gradient(135deg,#F5F3FF,#FCE7F3)}
.profile-info h2{font-size:1.2rem;font-weight:700;color:#1E1B2E}
.profile-info .profile-uuid{font-size:.72rem;color:#7C3AED;font-family:monospace;margin-top:2px}
.profile-info .profile-loc{font-size:.82rem;color:#6B7280;margin-top:4px}

/* ── Detail views ─────────────────────────────────────────── */
.detail-card{background:#fff;border:1px solid #E5E7EB;border-radius:16px;padding:28px;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.detail-photo-emoji{font-size:5rem;text-align:center;padding:32px 0;background:linear-gradient(135deg,#F5F3FF,#FCE7F3);border-radius:12px;margin-bottom:20px}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:600px){.detail-grid{grid-template-columns:1fr}}
.detail-label{font-size:.7rem;font-weight:700;color:#6B7280;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px}
.detail-value{font-size:.92rem;color:#1E1B2E;line-height:1.5}

.msg-bubble{background:#F5F3FF;border-radius:16px;padding:20px;margin:16px 0}
.msg-bubble .msg-direction{font-size:.72rem;font-weight:600;color:#7C3AED;margin-bottom:6px}
.msg-bubble .msg-body-text{font-size:.92rem;color:#1E1B2E;line-height:1.7;white-space:pre-wrap}
.msg-people{display:flex;gap:16px;margin-bottom:16px;padding:12px 16px;background:#F9FAFB;border-radius:12px}
.msg-person{display:flex;align-items:center;gap:8px;font-size:.85rem;color:#4B5563}
.msg-person strong{color:#1E1B2E}

/* ── Flag ─────────────────────────────────────────────────── */
.flag-banner{background:#FEF2F2;border:1px solid #FECACA;border-radius:12px;padding:18px 22px;margin-bottom:20px}
.flag-banner .flag-label{font-size:.7rem;font-weight:700;color:#DC2626;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;display:flex;align-items:center;gap:6px}
.flag-banner .flag-text{font-size:.82rem;color:#7F1D1D;margin-bottom:8px}
.flag-banner .flag-value{font-family:'Courier New',Courier,monospace;font-size:.95rem;font-weight:700;color:#991B1B;background:#FEF2F2;border:1px dashed #FCA5A5;padding:10px 14px;border-radius:8px;word-break:break-all}

.empty-state{text-align:center;padding:40px 20px;color:#9CA3AF;font-size:.9rem}
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:10px;font-size:.8rem;font-weight:600;border:none;cursor:pointer;transition:.15s;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,#7C3AED,#EC4899);color:#fff}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(124,58,237,.25);text-decoration:none}
.btn-ghost{background:transparent;color:#6B7280;border:1px solid #E5E7EB}
.btn-ghost:hover{color:#1E1B2E;border-color:#D1D5DB;text-decoration:none}
.btn-small{padding:4px 10px;font-size:.72rem}
.badge{display:inline-block;padding:2px 9px;border-radius:20px;font-size:.65rem;font-weight:600}
.badge-private{background:#FEF3C7;color:#92400E}
.badge-public{background:#ECFDF5;color:#047857}

.back-row{margin-bottom:14px;display:flex;gap:8px;flex-wrap:wrap}
</style>
</head>
<body>

<?php if (!$currentUser): ?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-logo">
      <div class="brand-icon">🌈</div>
      <div class="auth-title">FriendZone</div>
      <div class="auth-sub"><?= $isRegister ? 'Join the community!' : 'Welcome back to the Zone.' ?></div>
    </div>

    <?php if ($error): ?>
    <div class="error-msg"><?= esc($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= $isRegister ? '/index.php?action=register' : '/index.php' ?>">
      <?php if ($isRegister): ?>
      <input type="text" name="name" class="auth-input" placeholder="Full name" required>
      <input type="text" name="bio" class="auth-input" placeholder="Short bio (e.g. Cat lover & coffee addict)">
      <input type="text" name="location" class="auth-input" placeholder="Location (e.g. Austin, TX)">
      <?php endif; ?>
      <input type="email" name="email" class="auth-input" placeholder="Email address" required>
      <input type="password" name="password" class="auth-input" placeholder="Password" required>
      <button type="submit" class="auth-btn"><?= $isRegister ? 'Create Account' : 'Sign In' ?></button>
    </form>

    <div class="auth-switch">
      <?= $isRegister ? 'Already have an account? <a href="/index.php">Sign in</a>' : 'New here? <a href="/index.php?action=register">Join FriendZone</a>' ?>
    </div>

    <?php if (!$isRegister): ?>
    <div class="demo-box">
      <strong>🌐 Demo Users:</strong><br>
      alice@friendzone.com / alice123 (🎨)<br>
      bob@friendzone.com / bob123 (☕)<br>
      chloe@friendzone.com / chloe123 (📸)
    </div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<div class="app">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <span class="brand-icon">🌈</span>
      <span class="brand-text">FriendZone</span>
    </div>
    <nav class="sidebar-nav">
      <a href="/index.php?action=dashboard" class="<?= $isDash ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        Home
      </a>
      <a href="/index.php?action=explore" class="<?= $isExplr ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>
        Explore
      </a>
      <a href="/index.php?action=messages" class="<?= $isMsgs || $isMsg ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Messages<?php if ($unreadCount > 0): ?><span class="badge-count"><?= (int)$unreadCount ?></span><?php endif; ?>
      </a>
      <a href="/index.php?action=profile&uuid=<?= urlencode($currentUser['user_uuid']) ?>" class="<?= $isProfile ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        My Profile
      </a>
      <a href="/index.php?logout=1" style="margin-top:auto;color:#6B7280;">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sign Out
      </a>
    </nav>
    <div class="sidebar-footer">
      FriendZone v1.0 Beta &middot; &copy; 2026
    </div>
  </aside>

  <main class="main">
    <div class="topbar">
      <h1><?php
        if ($isExplr) echo 'Explore';
        elseif ($isMsgs || $isMsg) echo 'Messages';
        elseif ($isProfile) echo 'Profile';
        else echo 'Home';
      ?></h1>
      <div class="user-pill">
        <span class="user-avatar"><?= esc($currentUser['avatar_emoji']) ?></span>
        <?= esc($currentUser['name']) ?>
        <span style="color:#9CA3AF;font-size:.68rem;">UUID: <?= esc($currentUser['user_uuid']) ?></span>
      </div>
    </div>

<?php if ($isDash): ?>
    <?php
    $photoCount = 0; $msgCount = 0;
    $r = $db->query("SELECT COUNT(*) AS c FROM lab704_photos WHERE user_id = {$currentUser['id']}");
    if ($r) $photoCount = $r->fetch_assoc()['c'];
    $r = $db->query("SELECT COUNT(*) AS c FROM lab704_messages WHERE from_user_id = {$currentUser['id']} OR to_user_id = {$currentUser['id']}");
    if ($r) $msgCount = $r->fetch_assoc()['c'];
    ?>
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-emoji">📸</div><div class="stat-value"><?= (int)$photoCount ?></div><div class="stat-label">Photos</div></div>
      <div class="stat-card"><div class="stat-emoji">💬</div><div class="stat-value"><?= (int)$msgCount ?></div><div class="stat-label">Conversations</div></div>
      <div class="stat-card"><div class="stat-emoji">👥</div><div class="stat-value"><?= count($friendSuggestions) ?></div><div class="stat-label">Suggestions</div></div>
      <div class="stat-card"><div class="stat-emoji">📬</div><div class="stat-value"><?= (int)$unreadCount ?></div><div class="stat-label">Unread</div></div>
    </div>

    <div class="feed-grid">
      <div>
        <div class="card">
          <div class="card-title"><span class="card-icon">🔥</span> Recent Activity</div>
          <?php if ($activityFeed): ?>
            <?php foreach ($activityFeed as $act): ?>
            <div class="activity-item">
              <div class="activity-avatar"><?= esc($act['avatar_emoji']) ?></div>
              <div class="activity-content">
                <strong><?= esc($act['owner_name']) ?></strong> shared a photo
                <div style="font-size:.8rem;color:#6B7280;">"<?= esc($act['caption']) ?>"</div>
                <span class="activity-uuid">📌 UUID: <?= esc($act['photo_uuid']) ?></span>
              </div>
              <div style="text-align:right;flex-shrink:0;">
                <a href="/index.php?action=photo&uuid=<?= urlencode($act['photo_uuid']) ?>" class="btn btn-ghost btn-small">View</a>
                <div class="activity-time" style="margin-top:4px;"><?= date('M j', strtotime($act['created_at'])) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php else: ?>
          <div class="empty-state">No recent activity.</div>
          <?php endif; ?>
        </div>

        <div class="card">
          <div class="card-title"><span class="card-icon">📸</span> My Photos</div>
          <?php if ($myPhotos): ?>
          <div class="photo-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr))">
            <?php foreach (array_slice($myPhotos, 0, 4) as $p): ?>
            <div class="photo-card">
              <div class="photo-emoji" style="height:80px;font-size:2rem"><?= esc($p['image_emoji']) ?></div>
              <div class="photo-body" style="padding:10px">
                <div class="photo-caption" style="font-size:.75rem"><?= esc(mb_strimwidth($p['caption'], 0, 40, '…')) ?></div>
                <div class="photo-meta">
                  <span><?= $p['is_private'] ? '<span class="badge badge-private">Private</span>' : '<span class="badge badge-public">Public</span>' ?></span>
                  <a href="/index.php?action=photo&uuid=<?= urlencode($p['photo_uuid']) ?>" class="btn btn-ghost btn-small">View</a>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <div class="empty-state">No photos yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <div class="card">
          <div class="card-title"><span class="card-icon">👥</span> People You May Know</div>
          <?php foreach ($friendSuggestions as $s): ?>
          <div class="suggest-card">
            <div class="suggest-avatar"><?= esc($s['avatar_emoji']) ?></div>
            <div class="suggest-info">
              <div class="suggest-name"><?= esc($s['name']) ?></div>
              <div class="suggest-bio"><?= esc(mb_strimwidth($s['bio'] ?? '', 0, 50, '…')) ?></div>
              <div class="suggest-uuid">UUID: <?= esc($s['user_uuid']) ?></div>
            </div>
            <a href="/index.php?action=profile&uuid=<?= urlencode($s['user_uuid']) ?>" class="btn btn-ghost btn-small" style="flex-shrink:0">View</a>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="card">
          <div class="card-title"><span class="card-icon">💡</span> Quick Tips</div>
          <div style="font-size:.8rem;color:#6B7280;line-height:1.7;">
            <p>✨ FriendZone uses <strong>UUIDs</strong> to identify all content — photos, messages, and profiles.</p>
            <p style="margin-top:8px;">🔍 Each photo has a unique UUID like <code style="background:#F5F3FF;padding:1px 5px;border-radius:4px;font-size:.7rem;">a1b2c3d4-e5f6-47a8-b9c0-123456789abc</code></p>
            <p style="margin-top:8px;">🔒 Private photos are only visible to you... or are they?</p>
            <p style="margin-top:8px;">🆔 Decode UUIDs at <strong>uuidtools.com</strong> or <strong>uuiddecode.com</strong></p>
          </div>
        </div>
      </div>
    </div>

<?php elseif ($isExplr): ?>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-title"><span class="card-icon">🌍</span> Public Gallery</div>
      <p style="font-size:.82rem;color:#6B7280;margin-bottom:16px;">Recent uploads from the FriendZone community. Click any photo to view details.</p>
      <?php if ($publicPhotos): ?>
      <div class="photo-grid">
        <?php foreach ($publicPhotos as $p): ?>
        <div class="photo-card">
          <div class="photo-emoji"><?= esc($p['image_emoji']) ?></div>
          <div class="photo-body">
            <div class="photo-caption"><?= esc(mb_strimwidth($p['caption'], 0, 60, '…')) ?></div>
            <div class="photo-meta">
              <span class="photo-owner"><?= esc($p['avatar_emoji']) ?> <?= esc($p['owner_name']) ?></span>
              <span class="badge badge-public">Public</span>
            </div>
            <div class="photo-uuid-display">UUID: <?= esc($p['photo_uuid']) ?></div>
            <div style="margin-top:8px;">
              <a href="/index.php?action=photo&uuid=<?= urlencode($p['photo_uuid']) ?>" class="btn btn-primary btn-small">View Photo</a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">No public photos yet.</div>
      <?php endif; ?>
    </div>

<?php elseif ($isPhoto && $photoDetail): ?>
    <div class="back-row">
      <a href="/index.php?action=explore" class="btn btn-ghost">&larr; Gallery</a>
      <a href="/index.php?action=dashboard" class="btn btn-ghost">Home</a>
    </div>

    <?php if ($photoDetail['is_private'] && $photoDetail['user_id'] !== $currentUser['id']): ?>
    <div class="flag-banner">
      <div class="flag-label">🔓 PRIVATE PHOTO ACCESSED VIA IDOR</div>
      <div class="flag-text">This photo is marked as <strong>private</strong> and belongs to <strong><?= esc($photoDetail['owner_name']) ?></strong>. You were able to view it because the application uses UUIDs for access but does not verify ownership. The UUID was guessable or discoverable through enumeration.</div>
    </div>
    <?php endif; ?>

    <div class="detail-card">
      <div class="detail-photo-emoji"><?= esc($photoDetail['image_emoji']) ?></div>
      <div style="text-align:center;margin-bottom:20px;">
        <h2 style="font-size:1.1rem;color:#1E1B2E;"><?= esc($photoDetail['caption']) ?></h2>
        <div style="font-size:.75rem;color:#6B7280;margin-top:6px;">
          by <?= esc($photoDetail['avatar_emoji']) ?> <?= esc($photoDetail['owner_name']) ?>
          &middot; UUID: <code style="background:#F5F3FF;padding:1px 5px;border-radius:4px;"><?= esc($photoDetail['photo_uuid']) ?></code>
        </div>
        <div style="margin-top:8px;">
          <?= $photoDetail['is_private'] ? '<span class="badge badge-private">🔒 Private</span>' : '<span class="badge badge-public">🌍 Public</span>' ?>
        </div>
      </div>

      <div class="detail-grid">
        <div class="detail-field" style="background:#F9FAFB;border-radius:10px;padding:14px;">
          <div class="detail-label">Uploaded</div>
          <div class="detail-value"><?= date('F j, Y g:i A', strtotime($photoDetail['created_at'])) ?></div>
        </div>
        <div class="detail-field" style="background:#F9FAFB;border-radius:10px;padding:14px;">
          <div class="detail-label">Owner UUID</div>
          <div class="detail-value"><code style="background:#F5F3FF;padding:2px 6px;border-radius:4px;font-size:.82rem;"><?= esc($photoDetail['owner_uuid']) ?></code></div>
        </div>
      </div>
    </div>

<?php elseif ($isPhoto): ?>
    <div class="card"><div class="empty-state">📷 Photo not found. Try a different UUID.</div></div>

<?php elseif ($isMsgs): ?>
    <div class="card">
      <div class="card-title"><span class="card-icon">💬</span> My Conversations</div>
      <?php if ($myMessages): ?>
        <?php foreach ($myMessages as $m):
          $otherName = ($m['from_user_id'] == $currentUser['id']) ? $m['to_name'] : $m['from_name'];
          $otherEmoji = ($m['from_user_id'] == $currentUser['id']) ? $m['to_emoji'] : $m['from_emoji'];
          $isIncoming = ($m['to_user_id'] == $currentUser['id']);
        ?>
        <a href="/index.php?action=message&uuid=<?= urlencode($m['msg_uuid']) ?>" style="text-decoration:none;color:inherit;">
          <div class="msg-item">
            <div class="msg-avatar"><?= esc($otherEmoji) ?></div>
            <div class="msg-body">
              <div class="msg-header">
                <span class="msg-from"><?= esc($otherName) ?> <?= $isIncoming && !$m['is_read'] ? '<span style="color:#7C3AED;font-size:.6rem;">● New</span>' : '' ?></span>
                <span class="msg-date"><?= date('M j', strtotime($m['created_at'])) ?></span>
              </div>
              <div class="msg-subject"><?= esc($m['subject']) ?></div>
              <div class="msg-preview"><?= esc(mb_strimwidth($m['body'], 0, 90, '…')) ?></div>
              <div class="msg-uuid-tag">UUID: <?= esc($m['msg_uuid']) ?></div>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      <?php else: ?>
      <div class="empty-state">No messages yet. Start a conversation!</div>
      <?php endif; ?>
    </div>

<?php elseif ($isMsg && $msgDetail): ?>
    <div class="back-row">
      <a href="/index.php?action=messages" class="btn btn-ghost">&larr; All Messages</a>
    </div>

    <?php
    $isSelfMsg = ($msgDetail['from_user_id'] == $currentUser['id'] || $msgDetail['to_user_id'] == $currentUser['id']);
    $isAdminMsg = ($msgDetail['from_user_id'] == $msgDetail['to_user_id']);
    ?>

    <?php if (!$isSelfMsg): ?>
    <div class="flag-banner">
      <div class="flag-label">🔓 PRIVATE MESSAGE ACCESSED VIA IDOR</div>
      <div class="flag-text">This message is a private conversation between <strong><?= esc($msgDetail['from_name']) ?></strong> and <strong><?= esc($msgDetail['to_name']) ?></strong>. It does not belong to you. You were able to read it because the application uses UUIDs for access but does not verify that you are a participant in this conversation.</div>
      <?php if (str_starts_with($msgDetail['body'], 'flag{')): ?>
      <div class="flag-value"><?= esc($msgDetail['body']) ?></div>
      <?php endif; ?>
    </div>
    <?php elseif ($isAdminMsg && $currentUser['id'] != $msgDetail['from_user_id']): ?>
    <div class="flag-banner">
      <div class="flag-label">🔓 SECURITY NOTIFICATION ACCESSED</div>
      <div class="flag-text">This is a confidential security notification sent to another user. It contains sensitive account information.</div>
      <?php if (str_starts_with($msgDetail['body'], 'flag{')): ?>
      <div class="flag-value"><?= esc($msgDetail['body']) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="detail-card">
      <h2 style="font-size:1.1rem;font-weight:700;color:#1E1B2E;margin-bottom:4px;"><?= esc($msgDetail['subject']) ?></h2>
      <div style="font-size:.78rem;color:#6B7280;margin-bottom:16px;">UUID: <code style="background:#F5F3FF;padding:1px 5px;border-radius:4px;"><?= esc($msgDetail['msg_uuid']) ?></code></div>

      <div class="msg-people">
        <div class="msg-person"><span style="font-size:1.2rem;"><?= esc($msgDetail['from_emoji']) ?></span> <strong><?= esc($msgDetail['from_name']) ?></strong> <span style="color:#9CA3AF;font-size:.72rem;">(From)</span></div>
        <span style="color:#9CA3AF;">→</span>
        <div class="msg-person"><span style="font-size:1.2rem;"><?= esc($msgDetail['to_emoji']) ?></span> <strong><?= esc($msgDetail['to_name']) ?></strong> <span style="color:#9CA3AF;font-size:.72rem;">(To)</span></div>
      </div>

      <div class="msg-bubble">
        <div class="msg-direction">📩 Message sent on <?= date('F j, Y g:i A', strtotime($msgDetail['created_at'])) ?></div>
        <div class="msg-body-text"><?= nl2br(esc($msgDetail['body'])) ?></div>
      </div>
    </div>

<?php elseif ($isMsg): ?>
    <div class="card"><div class="empty-state">💬 Message not found. Try a different UUID.</div></div>

<?php elseif ($isProfile && $profileUser): ?>
    <?php
    $photoC = 0;
    $r = $db->query("SELECT COUNT(*) AS c FROM lab704_photos WHERE user_id = {$profileUser['id']}");
    if ($r) $photoC = $r->fetch_assoc()['c'];
    $profPhotos = [];
    $r = $db->query("SELECT * FROM lab704_photos WHERE user_id = {$profileUser['id']} ORDER BY created_at DESC LIMIT 6");
    if ($r) { while ($row = $r->fetch_assoc()) $profPhotos[] = $row; }
    ?>
    <div class="back-row">
      <a href="/index.php?action=dashboard" class="btn btn-ghost">&larr; Home</a>
    </div>

    <div class="detail-card">
      <div class="profile-header">
        <div class="profile-avatar-lg"><?= esc($profileUser['avatar_emoji']) ?></div>
        <div class="profile-info">
          <h2><?= esc($profileUser['name']) ?></h2>
          <div class="profile-uuid">UUID: <?= esc($profileUser['user_uuid']) ?></div>
          <div class="profile-loc"><?= esc($profileUser['location'] ?? 'Location not set') ?> <?= $profileUser['is_private'] ? '• 🔒 Private account' : '' ?></div>
        </div>
      </div>

      <div style="padding:14px 16px;background:#F9FAFB;border-radius:12px;margin-bottom:20px;">
        <div style="font-size:.7rem;font-weight:700;color:#6B7280;text-transform:uppercase;margin-bottom:6px;">Bio</div>
        <div style="font-size:.9rem;color:#1E1B2E;"><?= esc($profileUser['bio'] ?? 'No bio yet.') ?></div>
      </div>

      <div class="detail-grid" style="margin-bottom:20px;">
        <div class="detail-field" style="background:#F9FAFB;border-radius:10px;padding:14px;">
          <div class="detail-label">Location</div>
          <div class="detail-value"><?= esc($profileUser['location'] ?? '—') ?></div>
        </div>
        <div class="detail-field" style="background:#F9FAFB;border-radius:10px;padding:14px;">
          <div class="detail-label">Photos</div>
          <div class="detail-value"><?= (int)$photoC ?> uploaded</div>
        </div>
        <div class="detail-field" style="background:#F9FAFB;border-radius:10px;padding:14px;">
          <div class="detail-label">Joined</div>
          <div class="detail-value"><?= date('F Y', strtotime($profileUser['created_at'])) ?></div>
        </div>
        <div class="detail-field" style="background:#F9FAFB;border-radius:10px;padding:14px;">
          <div class="detail-label">Account Type</div>
          <div class="detail-value"><?= $profileUser['is_private'] ? '🔒 Private' : '🌍 Public' ?></div>
        </div>
      </div>

      <?php if ($profPhotos): ?>
      <div style="margin-top:8px;">
        <div style="font-size:.82rem;font-weight:600;color:#1E1B2E;margin-bottom:12px;">📸 Recent Photos</div>
        <div class="photo-grid" style="grid-template-columns:repeat(auto-fill,minmax(130px,1fr))">
          <?php foreach ($profPhotos as $p): ?>
          <div class="photo-card">
            <div class="photo-emoji" style="height:70px;font-size:1.8rem"><?= esc($p['image_emoji']) ?></div>
            <div class="photo-body" style="padding:10px">
              <div class="photo-caption" style="font-size:.72rem"><?= esc(mb_strimwidth($p['caption'], 0, 40, '…')) ?></div>
              <div class="photo-meta">
                <?= $p['is_private'] ? '<span class="badge badge-private">Private</span>' : '<span class="badge badge-public">Public</span>' ?>
                <a href="/index.php?action=photo&uuid=<?= urlencode($p['photo_uuid']) ?>" class="btn btn-ghost btn-small">View</a>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

<?php elseif ($isProfile): ?>
    <div class="card"><div class="empty-state">👤 User not found. Try a different UUID.</div></div>

<?php endif; ?>
  </main>
</div>
<?php endif; ?>

</body>
</html>