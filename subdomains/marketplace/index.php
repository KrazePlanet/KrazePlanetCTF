<?php
// ContentForge Marketplace — buy SEO articles by word count
// A realistic content-marketplace site used for hands-on web-security practice.
// Students register real accounts, load their wallet, and buy article packages
// in bulk (pick quantities across several word-count tiers in one order).
//
// The intended flaw (mirrors HackerOne #771694, Semrush Marketplace): the bulk
// purchase endpoint receives { "items": { "<sku>": <quantity>, ... } } and prices
// each line using the catalog's own authoritative price-per-sku (so the price
// itself can't be tampered with) — but it never checks that quantity is positive.
// A shopper who edits the outgoing request can set one line's quantity negative,
// which subtracts that line's cost from the order total instead of adding to it,
// letting a handful of articles be bought for a small fraction of their real price.

session_start();

$db_host     = getenv('DB_HOST') ?: 'localhost';
$db_username = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASS') ?: '';
$db_name     = getenv('DB_NAME') ?: 'KrazePlanet';

mysqli_report(MYSQLI_REPORT_OFF);
$db = @new mysqli($db_host, $db_username, $db_password);
if ($db->connect_error) { die('Service temporarily unavailable.'); }
$db->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->select_db($db_name);
$db->set_charset('utf8mb4');

$db->query("CREATE TABLE IF NOT EXISTS mkt_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    balance_cents INT NOT NULL DEFAULT 30000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS mkt_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT '',
    price_cents INT NOT NULL,
    turnaround_days INT NOT NULL DEFAULT 3,
    image_url VARCHAR(255) DEFAULT ''
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS mkt_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_uuid VARCHAR(64) NOT NULL,
    items_json TEXT NOT NULL,
    total_cents INT NOT NULL,
    balance_after_cents INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($cents) { $neg = $cents < 0; return ($neg ? '-$' : '$') . number_format(abs((int)$cents) / 100, 2); }
function uuidv4() {
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
    $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}

// ── Seed / backfill catalog (authoritative prices) ───────────────────────────────
$seed = [
    ['article_500',  '500 Words',  'Standard SEO article, 500 words', 4000, 3, 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=500&fit=crop&q=80'],
    ['article_1000', '1000 Words', 'Standard SEO article, 1000 words', 7000, 4, 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=500&fit=crop&q=80'],
    ['article_1500', '1500 Words', 'Standard SEO article, 1500 words', 10000, 5, 'https://images.unsplash.com/photo-1517842645767-c639042777db?w=500&fit=crop&q=80'],
    ['article_2000', '2000 Words', 'Standard SEO article, 2000 words', 13000, 6, 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=500&fit=crop&q=80'],
    ['article_3000', '3000 Words', 'Standard SEO article, 3000 words', 18000, 8, 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?w=500&fit=crop&q=80'],
    ['expert_500',   'Expert Writer — 500 Words',  'Subject-matter expert, 500 words', 6000, 4, 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=500&fit=crop&q=80'],
    ['expert_1000',  'Expert Writer — 1000 Words', 'Subject-matter expert, 1000 words', 9500, 5, 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=500&fit=crop&q=80'],
];
$offerCheck = $db->query("SELECT COUNT(*) AS c FROM mkt_offers")->fetch_assoc();
if ((int)$offerCheck['c'] === 0) {
    $st = $db->prepare("INSERT INTO mkt_offers (sku, name, description, price_cents, turnaround_days, image_url) VALUES (?,?,?,?,?,?)");
    foreach ($seed as $s) { $st->bind_param('sssiis', $s[0], $s[1], $s[2], $s[3], $s[4], $s[5]); $st->execute(); }
} else {
    $st = $db->prepare("UPDATE mkt_offers SET image_url=? WHERE sku=? AND (image_url IS NULL OR image_url='')");
    foreach ($seed as $s) { $st->bind_param('ss', $s[5], $s[0]); $st->execute(); }
}

function current_user($db) {
    if (empty($_SESSION['uid'])) return null;
    $st = $db->prepare("SELECT * FROM mkt_users WHERE id=?");
    $st->bind_param('i', $_SESSION['uid']); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function all_offers($db) {
    $out = []; $r = $db->query("SELECT * FROM mkt_offers ORDER BY price_cents ASC");
    while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function offer_by_sku($db, $sku) {
    $st = $db->prepare("SELECT * FROM mkt_offers WHERE sku=?");
    $st->bind_param('s', $sku); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function offer_by_id($db, $id) {
    $st = $db->prepare("SELECT * FROM mkt_offers WHERE id=?");
    $st->bind_param('i', $id); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function user_orders($db, $userId) {
    $st = $db->prepare("SELECT * FROM mkt_orders WHERE user_id=? ORDER BY id DESC");
    $st->bind_param('i', $userId); $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function get_order($db, $id, $userId) {
    $st = $db->prepare("SELECT * FROM mkt_orders WHERE id=? AND user_id=?");
    $st->bind_param('ii', $id, $userId); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function first_name($name) { $p = preg_split('/\s+/', trim($name)); return $p[0] ?: $name; }
function avatar_letter($name) { return strtoupper(mb_substr(trim($name), 0, 1) ?: 'U'); }

function order_cart() { if (!isset($_SESSION['order_cart']) || !is_array($_SESSION['order_cart'])) $_SESSION['order_cart'] = []; return $_SESSION['order_cart']; }

$action = $_GET['action'] ?? 'marketplace';
$me = current_user($db);

// ── Auth ──────────────────────────────────────────────────────────────────────
if ($action === 'logout') { session_destroy(); header('Location: index.php?action=login'); exit; }

$authError = '';
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $authError = 'Enter your name, a valid email, and a password of at least 6 characters.';
        } else {
            $st = $db->prepare("SELECT id FROM mkt_users WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            if ($st->get_result()->fetch_row()) {
                $authError = 'An account with that email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $st = $db->prepare("INSERT INTO mkt_users (name, email, password_hash) VALUES (?,?,?)");
                $st->bind_param('sss', $name, $email, $hash); $st->execute();
                $_SESSION['uid'] = $db->insert_id;
                header('Location: index.php'); exit;
            }
        }
    }
    render_auth('register', $authError);
    exit;
}
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $st = $db->prepare("SELECT * FROM mkt_users WHERE email=?");
        $st->bind_param('s', $email); $st->execute();
        $u = $st->get_result()->fetch_assoc();
        if ($u && password_verify($pass, $u['password_hash'])) {
            $_SESSION['uid'] = $u['id'];
            header('Location: ' . ($_GET['next'] ?? 'index.php')); exit;
        }
        $authError = 'Invalid email or password.';
    }
    render_auth('login', $authError);
    exit;
}

if (!$me) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
    header('Location: index.php?action=login&next=' . $next);
    exit;
}
$myId = (int)$me['id'];

// ── Order builder cart (server-authoritative, used by the normal UI) ────────────
if ($action === 'add-to-order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['offer_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if (offer_by_id($db, $oid)) {
        $c = order_cart(); $c[$oid] = ($c[$oid] ?? 0) + $qty; $_SESSION['order_cart'] = $c;
    }
    header('Location: index.php?action=marketplace&added=1'); exit;
}
if ($action === 'update-order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $c = order_cart();
    foreach ($_POST['qty'] ?? [] as $oid => $qty) {
        $oid = (int)$oid; $qty = max(0, (int)$qty);
        if ($qty === 0) unset($c[$oid]); else $c[$oid] = $qty;
    }
    $_SESSION['order_cart'] = $c;
    header('Location: index.php?action=review-order'); exit;
}
if ($action === 'remove-from-order') {
    $oid = (int)($_GET['id'] ?? 0);
    $c = order_cart(); unset($c[$oid]); $_SESSION['order_cart'] = $c;
    header('Location: index.php?action=review-order'); exit;
}

// ── Add funds (fake top-up) ──────────────────────────────────────────────────────
if ($action === 'add-funds' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (int)($_POST['amount_cents'] ?? 0);
    if ($amount > 0 && $amount <= 100000) {
        $st = $db->prepare("UPDATE mkt_users SET balance_cents = balance_cents + ? WHERE id=?");
        $st->bind_param('ii', $amount, $myId); $st->execute();
    }
    header('Location: index.php?action=wallet'); exit;
}

// ── VULNERABLE ENDPOINT — bulk purchase ──────────────────────────────────────────
// Mirrors: POST /marketplace/api/purchases/bulk  { "items": { "<sku>": <qty>, ... } }
// Each line's PRICE always comes from the server's own catalog (mkt_offers), so an
// attacker cannot rewrite a unit price directly. But nothing here rejects a
// negative quantity, so a negative line SUBTRACTS from the running total instead
// of adding to it.
if ($action === 'purchases-bulk') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'method not allowed']); exit; }

    $body = json_decode(file_get_contents('php://input'), true);
    $items = is_array($body) ? ($body['items'] ?? null) : null;
    if (!is_array($items) || count($items) === 0) {
        http_response_code(400); echo json_encode(['error' => 'Request must include at least one item.']); exit;
    }

    $lines = [];
    $total = 0;
    foreach ($items as $sku => $qty) {
        $offer = offer_by_sku($db, $sku);
        if (!$offer) { http_response_code(422); echo json_encode(['error' => "Unknown item: $sku"]); exit; }
        $qty = (int)$qty;
        if ($qty === 0) continue;

        // --- THE BUG -------------------------------------------------------------
        // No check that $qty > 0. The unit price is always the server's own
        // mkt_offers.price_cents (so price tampering isn't possible here), but a
        // negative quantity still legitimately subtracts from $total below.
        $lineTotal = $offer['price_cents'] * $qty;
        // ---------------------------------------------------------------------

        $total += $lineTotal;
        $lines[] = ['sku' => $sku, 'name' => $offer['name'], 'unit_price' => $offer['price_cents'], 'quantity' => $qty, 'line_total' => $lineTotal];
    }
    if (empty($lines)) { http_response_code(400); echo json_encode(['error' => 'Request must include at least one item.']); exit; }

    $total = max(0, $total); // never charge a negative amount, but $0 is allowed
    $balance = (int)$me['balance_cents'];
    if ($total > $balance) {
        http_response_code(402); echo json_encode(['error' => 'Insufficient balance.']); exit;
    }

    $newBalance = $balance - $total;
    $st = $db->prepare("UPDATE mkt_users SET balance_cents=? WHERE id=?");
    $st->bind_param('ii', $newBalance, $myId); $st->execute();

    $orderUuid = uuidv4();
    $itemsJson = json_encode($lines);
    $st = $db->prepare("INSERT INTO mkt_orders (user_id, order_uuid, items_json, total_cents, balance_after_cents) VALUES (?,?,?,?,?)");
    $st->bind_param('issii', $myId, $orderUuid, $itemsJson, $total, $newBalance);
    $st->execute();
    $newId = $db->insert_id;

    $_SESSION['order_cart'] = [];
    echo json_encode(['status' => 'accepted', 'order_id' => $newId, 'order_uuid' => $orderUuid, 'total' => $total, 'balance_after' => $newBalance]);
    exit;
}

$offers = all_offers($db);
$offersById = [];
foreach ($offers as $o) $offersById[(int)$o['id']] = $o;
$cartArr = order_cart();
$cartCount = array_sum($cartArr);

// =========================================================================
//  RENDER
// =========================================================================
function render_auth($mode, $error) {
    $isReg = ($mode === 'register');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isReg ? 'Sign up' : 'Sign in'; ?> — ContentForge</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#f4f6fb;color:#1c2333;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.auth{width:400px;max-width:92vw;}
.logo-row{display:flex;align-items:center;gap:0.6rem;justify-content:center;font-family:'Sora',sans-serif;font-weight:700;font-size:1.4rem;margin-bottom:1.75rem;color:#1d4ed8;}
.logo-row .mark{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#1d4ed8,#0891b2);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:700;}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:2rem;box-shadow:0 2px 14px rgba(30,41,59,0.06);}
.card h1{font-size:1.3rem;margin:0 0 0.35rem;font-family:'Sora',sans-serif;}
.card p.s{color:#64748b;font-size:0.88rem;margin:0 0 1.5rem;}
label{display:block;font-size:0.8rem;color:#475569;margin:0.9rem 0 0.35rem;font-weight:500;}
input{width:100%;background:#f8fafc;border:1px solid #dbe3ee;color:#1c2333;border-radius:9px;padding:0.7rem 0.8rem;font-size:0.92rem;}
input:focus{outline:none;border-color:#1d4ed8;}
.btn-primary{width:100%;margin-top:1.4rem;background:#1d4ed8;border:none;color:#fff;padding:0.75rem;border-radius:9px;font-size:0.95rem;font-weight:600;cursor:pointer;}
.btn-primary:hover{background:#1743b8;}
.alt{text-align:center;margin-top:1.25rem;font-size:0.86rem;color:#64748b;}
.alt a{color:#1d4ed8;text-decoration:none;font-weight:600;}
.err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:0.7rem 0.9rem;border-radius:9px;font-size:0.85rem;margin-bottom:1rem;}
</style></head><body>
<div class="auth">
  <div class="logo-row"><span class="mark">CF</span> ContentForge</div>
  <div class="card">
    <h1><?php echo $isReg ? 'Create your account' : 'Welcome back'; ?></h1>
    <p class="s"><?php echo $isReg ? 'Order SEO content from vetted writers.' : 'Sign in to your marketplace account.'; ?></p>
    <?php if ($error): ?><div class="err"><?php echo esc($error); ?></div><?php endif; ?>
    <form method="POST" action="index.php?action=<?php echo $isReg ? 'register' : 'login'; ?><?php echo isset($_GET['next']) ? '&next=' . esc($_GET['next']) : ''; ?>">
      <?php if ($isReg): ?><label>Full name</label><input name="name" required><?php endif; ?>
      <label>Email</label><input name="email" type="email" placeholder="you@example.com" required>
      <label>Password</label><input name="password" type="password" placeholder="••••••••" required>
      <button class="btn-primary" type="submit"><?php echo $isReg ? 'Sign up' : 'Sign in'; ?></button>
    </form>
    <div class="alt">
      <?php if ($isReg): ?>Already have an account? <a href="index.php?action=login">Sign in</a>
      <?php else: ?>New here? <a href="index.php?action=register">Create an account</a><?php endif; ?>
    </div>
  </div>
</div>
</body></html>
<?php
}

function render_head($title) {
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?> — ContentForge</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#f4f6fb;color:#1c2333;font-family:'Inter',sans-serif;}
a{color:inherit;}
h1,h2{font-family:'Sora',sans-serif;}
::-webkit-scrollbar{width:8px;}
::-webkit-scrollbar-thumb{background:#dbe3ee;border-radius:4px;}
.topbar{position:sticky;top:0;background:#fff;border-bottom:1px solid #e2e8f0;z-index:20;}
.topbar-in{max-width:1080px;margin:0 auto;padding:0.85rem 1.5rem;display:flex;align-items:center;justify-content:space-between;}
.brand{display:flex;align-items:center;gap:0.55rem;font-family:'Sora',sans-serif;font-weight:700;font-size:1.15rem;color:#1d4ed8;text-decoration:none;}
.brand .mark{width:30px;height:30px;border-radius:8px;background:linear-gradient(135deg,#1d4ed8,#0891b2);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:700;}
.nav{display:flex;gap:1.4rem;align-items:center;}
.nav a{font-size:0.9rem;color:#475569;text-decoration:none;font-weight:500;}
.nav a.active,.nav a:hover{color:#1d4ed8;}
.nav .cart-link{position:relative;}
.nav .cart-badge{position:absolute;top:-8px;right:-14px;background:#1d4ed8;color:#fff;font-size:0.65rem;font-weight:700;border-radius:10px;padding:0.05rem 0.4rem;}
.acct{display:flex;align-items:center;gap:0.6rem;}
.acct .balance{font-size:0.82rem;font-weight:600;color:#0f766e;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:0.3rem 0.6rem;}
.acct .av{width:30px;height:30px;border-radius:50%;background:#1d4ed8;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.82rem;font-weight:700;}
.btn{background:#fff;border:1px solid #dbe3ee;color:#1c2333;padding:0.4rem 0.9rem;border-radius:8px;font-size:0.84rem;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;}
.btn:hover{background:#f1f5f9;}
.btn-primary{background:#1d4ed8;border:none;color:#fff;}
.btn-primary:hover{background:#1743b8;}
.wrap{max-width:1080px;margin:0 auto;padding:2rem 1.5rem;}
.hero{position:relative;color:#fff;padding:3rem 1.5rem;margin-bottom:1rem;overflow:hidden;}
.hero img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:brightness(0.42);}
.hero-in{position:relative;max-width:1080px;margin:0 auto;}
.hero h1{font-size:2rem;margin:0 0 0.4rem;}
.hero p{opacity:0.92;margin:0;font-size:0.95rem;}
.cat-title{font-size:1.2rem;margin:1.75rem 0 0.9rem;}
.offer-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;}
.ocard{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;}
.ocard .thumb{height:120px;overflow:hidden;}
.ocard .thumb img{width:100%;height:100%;object-fit:cover;display:block;}
.ocard .body{padding:0.9rem 1rem;flex:1;display:flex;flex-direction:column;}
.ocard .nm{font-weight:700;font-size:0.98rem;}
.ocard .dsc{font-size:0.78rem;color:#64748b;margin-top:0.2rem;flex:1;}
.ocard .turn{font-size:0.72rem;color:#0891b2;margin-top:0.4rem;}
.ocard .foot{display:flex;align-items:center;justify-content:space-between;margin-top:0.75rem;}
.ocard .price{font-weight:700;color:#1d4ed8;}
.qty-add{display:flex;gap:0.35rem;align-items:center;}
.qty-add input{width:44px;text-align:center;border:1px solid #dbe3ee;border-radius:7px;padding:0.3rem;font-size:0.85rem;}
.add-btn{background:#1d4ed8;border:none;color:#fff;border-radius:7px;padding:0.35rem 0.7rem;font-size:0.8rem;cursor:pointer;font-weight:600;}
.add-btn:hover{background:#1743b8;}
.notice{background:#ecfdf5;border:1px solid #a7f3d0;color:#0f766e;padding:0.75rem 1rem;border-radius:10px;font-size:0.85rem;margin-bottom:1.25rem;}
.warn{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1rem;border-radius:10px;font-size:0.85rem;margin-bottom:1.25rem;}
.empty{color:#64748b;font-size:0.9rem;padding:2.5rem 0;text-align:center;}
.order-line{display:flex;align-items:center;gap:1rem;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:0.85rem 1rem;margin-bottom:0.6rem;}
.order-line img{width:52px;height:52px;border-radius:9px;object-fit:cover;flex-shrink:0;}
.order-line .nm{font-weight:600;flex:1;}
.order-line .unit{color:#64748b;font-size:0.8rem;}
.order-line input{width:56px;text-align:center;border:1px solid #dbe3ee;border-radius:7px;padding:0.3rem;}
.order-line .lt{font-weight:700;width:90px;text-align:right;}
.order-line .rm{color:#b91c1c;font-size:0.8rem;text-decoration:none;}
.summary{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem;position:sticky;top:90px;}
.summary .row{display:flex;justify-content:space-between;font-size:0.88rem;margin-bottom:0.5rem;color:#475569;}
.summary .row.total{font-weight:700;color:#1c2333;font-size:1.05rem;border-top:1px solid #e2e8f0;padding-top:0.6rem;margin-top:0.6rem;}
.layout{display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;}
@media(max-width:820px){.layout{grid-template-columns:1fr;}}
.receipt{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.5rem;}
.receipt .conf{font-size:0.8rem;color:#64748b;}
.receipt table{width:100%;border-collapse:collapse;margin-top:1rem;}
.receipt td{padding:0.4rem 0;font-size:0.88rem;border-bottom:1px solid #f1f5f9;}
.receipt td.r{text-align:right;}
.order-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem;margin-bottom:0.75rem;}
.order-card .hd{display:flex;justify-content:space-between;align-items:center;}
.order-card .hd .cc{font-weight:700;font-size:0.85rem;color:#64748b;}
.order-card .hd .amt{font-weight:700;color:#1d4ed8;}
.order-card .meta{font-size:0.78rem;color:#64748b;margin-top:0.2rem;}
.fund-opt{display:flex;gap:0.6rem;margin-bottom:1rem;}
.fund-btn{flex:1;text-align:center;padding:0.7rem;border:1px solid #dbe3ee;border-radius:10px;cursor:pointer;font-weight:600;background:#fff;}
.fund-btn:hover{background:#f1f5f9;}
.wallet-hero{position:relative;border-radius:16px;overflow:hidden;color:#fff;padding:1.75rem;margin-bottom:1.5rem;}
.wallet-hero img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:brightness(0.4);}
.wallet-hero .in{position:relative;}
.wallet-hero .bal{font-size:2rem;font-weight:800;font-family:'Sora',sans-serif;}
</style></head><body>
<?php
}

function render_topbar($active, $me, $cartCount) {
?>
<div class="topbar"><div class="topbar-in">
  <a href="index.php?action=marketplace" class="brand"><span class="mark">CF</span> ContentForge</a>
  <div class="nav">
    <a href="index.php?action=marketplace" class="<?php echo $active==='marketplace'?'active':''; ?>">Marketplace</a>
    <a href="index.php?action=orders" class="<?php echo $active==='orders'?'active':''; ?>">Orders</a>
    <a href="index.php?action=wallet" class="<?php echo $active==='wallet'?'active':''; ?>">Wallet</a>
    <a href="index.php?action=review-order" class="cart-link <?php echo $active==='review-order'?'active':''; ?>">Order builder<?php if ($cartCount>0): ?><span class="cart-badge"><?php echo (int)$cartCount; ?></span><?php endif; ?></a>
  </div>
  <div class="acct">
    <span class="balance"><?php echo money($me['balance_cents']); ?></span>
    <span class="av"><?php echo esc(avatar_letter($me['name'])); ?></span>
    <span style="font-size:0.85rem;"><?php echo esc(first_name($me['name'])); ?></span>
    <a href="index.php?action=logout" class="btn">Sign out</a>
  </div>
</div></div>
<?php
}

// =========================================================================
//  PAGES
// =========================================================================

if ($action === 'wallet') {
    $funded = isset($_GET['funded']);
    render_head('Wallet'); render_topbar('wallet', $me, $cartCount);
    ?>
    <div class="wrap" style="max-width:560px;">
      <div class="wallet-hero">
        <img src="https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?w=900&fit=crop&q=80" alt="">
        <div class="in">
          <div style="font-size:0.85rem;opacity:0.85;">Available balance</div>
          <div class="bal"><?php echo money($me['balance_cents']); ?></div>
        </div>
      </div>
      <?php if ($funded): ?><div class="notice">Funds added to your wallet.</div><?php endif; ?>
      <h2 style="font-size:1.05rem;">Add funds</h2>
      <p style="color:#64748b;font-size:0.85rem;margin-top:-0.6rem;">Demo wallet — no real payment is processed.</p>
      <form method="POST" action="index.php?action=add-funds" id="fundForm">
        <input type="hidden" name="amount_cents" id="fundAmount" value="5000">
        <div class="fund-opt">
          <div class="fund-btn" onclick="pick(5000,this)">+$50</div>
          <div class="fund-btn" onclick="pick(10000,this)">+$100</div>
          <div class="fund-btn" onclick="pick(25000,this)">+$250</div>
        </div>
        <button class="btn btn-primary" style="width:100%;justify-content:center;" type="submit">Add funds</button>
      </form>
      <script>
      function pick(v, el){ document.getElementById('fundAmount').value = v; document.querySelectorAll('.fund-btn').forEach(b=>b.style.background=''); el.style.background='#e0e7ff'; }
      </script>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'review-order') {
    global $offersById;
    $lines = [];
    $total = 0;
    foreach ($cartArr as $oid => $qty) {
        if (!isset($offersById[$oid])) continue;
        $o = $offersById[$oid];
        $lineTotal = $o['price_cents'] * $qty;
        $total += $lineTotal;
        $lines[] = ['offer' => $o, 'qty' => $qty, 'total' => $lineTotal];
    }
    render_head('Order builder'); render_topbar('review-order', $me, $cartCount);
    if (empty($lines)) {
        echo '<div class="wrap"><div class="empty">Your order is empty. <a href="index.php?action=marketplace" style="color:#1d4ed8;">Browse the marketplace</a>.</div></div></body></html>';
        exit;
    }
    ?>
    <div class="wrap">
      <h1 style="font-size:1.4rem;">Review your order</h1>
      <div class="layout">
        <div>
          <form method="POST" action="index.php?action=update-order">
            <?php foreach ($lines as $l): $o = $l['offer']; ?>
            <div class="order-line">
              <img src="<?php echo esc($o['image_url']); ?>" alt="">
              <div class="nm"><?php echo esc($o['name']); ?><div class="unit"><?php echo money($o['price_cents']); ?> each · <?php echo (int)$o['turnaround_days']; ?>-day turnaround</div></div>
              <input type="number" name="qty[<?php echo (int)$o['id']; ?>]" value="<?php echo (int)$l['qty']; ?>" min="1" max="50">
              <div class="lt"><?php echo money($l['total']); ?></div>
              <a class="rm" href="index.php?action=remove-from-order&id=<?php echo (int)$o['id']; ?>">Remove</a>
            </div>
            <?php endforeach; ?>
            <button class="btn" type="submit">Update quantities</button>
          </form>
        </div>
        <div class="summary">
          <div style="font-weight:700;margin-bottom:0.75rem;">Order summary</div>
          <?php foreach ($lines as $l): ?>
            <div class="row"><span><?php echo (int)$l['qty']; ?>× <?php echo esc($l['offer']['name']); ?></span><span><?php echo money($l['total']); ?></span></div>
          <?php endforeach; ?>
          <div class="row total"><span>Total</span><span id="s-total"><?php echo money($total); ?></span></div>
          <div style="font-size:0.78rem;color:#64748b;margin-top:0.5rem;">Wallet balance: <?php echo money($me['balance_cents']); ?></div>
          <button class="btn btn-primary" style="width:100%;justify-content:center;margin-top:1rem;" onclick="placeOrder()">Confirm &amp; pay</button>
          <div id="orderError" style="display:none;color:#b91c1c;font-size:0.82rem;margin-top:0.6rem;"></div>
        </div>
      </div>
    </div>
    <script>
    const LINES = <?php echo json_encode(array_map(function($l){ return ['sku'=>$l['offer']['sku'], 'quantity'=>(int)$l['qty']]; }, $lines)); ?>;
    function placeOrder(){
      const items = {};
      LINES.forEach(l => { items[l.sku] = l.quantity; });
      fetch('?action=purchases-bulk', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ items: items }) })
        .then(r => r.json().then(d => ({ok:r.ok, d})))
        .then(({ok,d}) => {
          if (!ok) { document.getElementById('orderError').style.display='block'; document.getElementById('orderError').textContent = d.error || 'Order could not be placed.'; return; }
          window.location.href = 'index.php?action=order&id=' + d.order_id;
        })
        .catch(()=>{ document.getElementById('orderError').style.display='block'; document.getElementById('orderError').textContent='Network error.'; });
    }
    </script>
    </body></html>
    <?php
    exit;
}

if ($action === 'order') {
    $oid = (int)($_GET['id'] ?? 0);
    $o = get_order($db, $oid, $myId);
    render_head('Order receipt'); render_topbar('orders', $me, $cartCount);
    if (!$o) { echo '<div class="wrap"><div class="empty">Order not found.</div></div></body></html>'; exit; }
    $itemsArr = json_decode($o['items_json'], true) ?: [];
    ?>
    <div class="wrap" style="max-width:560px;">
      <div class="notice">Order placed! <?php echo money($o['total_cents']); ?> was deducted from your wallet.</div>
      <div class="receipt">
        <div style="font-weight:700;font-size:1.05rem;">Order #<?php echo (int)$o['id']; ?></div>
        <div class="conf">Ref <?php echo esc($o['order_uuid']); ?> · Placed <?php echo esc($o['created_at']); ?></div>
        <table>
          <?php foreach ($itemsArr as $it): ?>
          <tr><td><?php echo (int)($it['quantity'] ?? 0); ?>× <?php echo esc($it['name'] ?? ''); ?> @ <?php echo money($it['unit_price'] ?? 0); ?></td><td class="r"><?php echo money($it['line_total'] ?? 0); ?></td></tr>
          <?php endforeach; ?>
          <tr><td style="font-weight:700;">Total charged</td><td class="r" style="font-weight:700;"><?php echo money($o['total_cents']); ?></td></tr>
          <tr><td>Wallet balance after</td><td class="r"><?php echo money($o['balance_after_cents']); ?></td></tr>
        </table>
      </div>
      <a href="index.php?action=orders" class="btn" style="margin-top:1rem;">← Back to orders</a>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'orders') {
    $orders = user_orders($db, $myId);
    render_head('Your orders'); render_topbar('orders', $me, $cartCount);
    ?>
    <div class="wrap" style="max-width:640px;">
      <h1 style="font-size:1.4rem;">Your orders</h1>
      <?php if (empty($orders)): ?>
        <div class="empty">No orders yet. <a href="index.php?action=marketplace" style="color:#1d4ed8;">Browse the marketplace.</a></div>
      <?php else: foreach ($orders as $o):
        $itemsArr = json_decode($o['items_json'], true) ?: [];
      ?>
      <a href="index.php?action=order&id=<?php echo (int)$o['id']; ?>" style="text-decoration:none;color:inherit;">
      <div class="order-card">
        <div class="hd"><span class="cc">Order #<?php echo (int)$o['id']; ?></span><span class="amt"><?php echo money($o['total_cents']); ?></span></div>
        <div class="meta"><?php echo count($itemsArr); ?> item<?php echo count($itemsArr)!==1?'s':''; ?> · <?php echo esc($o['created_at']); ?></div>
      </div>
      </a>
      <?php endforeach; endif; ?>
    </div>
    </body></html>
    <?php
    exit;
}

// ── Default: Marketplace ──────────────────────────────────────────────────────────
$added = isset($_GET['added']);
render_head('Marketplace'); render_topbar('marketplace', $me, $cartCount);
$standard = array_filter($offers, fn($o) => strpos($o['sku'], 'expert_') !== 0);
$expert   = array_filter($offers, fn($o) => strpos($o['sku'], 'expert_') === 0);
?>
<div class="hero">
  <img src="https://images.unsplash.com/photo-1455390582262-044cdead277a?w=1200&fit=crop&q=80" alt="">
  <div class="hero-in">
    <h1>Content Marketplace</h1>
    <p>Order SEO-ready articles by word count, written and delivered fast.</p>
  </div>
</div>
<div class="wrap">
  <?php if ($added): ?><div class="notice">Added to your order.</div><?php endif; ?>

  <h2 class="cat-title">Standard articles</h2>
  <div class="offer-grid">
    <?php foreach ($standard as $o): ?>
    <div class="ocard">
      <div class="thumb"><img src="<?php echo esc($o['image_url']); ?>" alt="<?php echo esc($o['name']); ?>" loading="lazy"></div>
      <div class="body">
        <div class="nm"><?php echo esc($o['name']); ?></div>
        <div class="dsc"><?php echo esc($o['description']); ?></div>
        <div class="turn"><?php echo (int)$o['turnaround_days']; ?>-day turnaround</div>
        <div class="foot">
          <span class="price"><?php echo money($o['price_cents']); ?></span>
          <form method="POST" action="index.php?action=add-to-order" class="qty-add">
            <input type="hidden" name="offer_id" value="<?php echo (int)$o['id']; ?>">
            <input type="number" name="qty" value="1" min="1" max="50">
            <button class="add-btn" type="submit">Order</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <h2 class="cat-title">Expert writers</h2>
  <div class="offer-grid">
    <?php foreach ($expert as $o): ?>
    <div class="ocard">
      <div class="thumb"><img src="<?php echo esc($o['image_url']); ?>" alt="<?php echo esc($o['name']); ?>" loading="lazy"></div>
      <div class="body">
        <div class="nm"><?php echo esc($o['name']); ?></div>
        <div class="dsc"><?php echo esc($o['description']); ?></div>
        <div class="turn"><?php echo (int)$o['turnaround_days']; ?>-day turnaround</div>
        <div class="foot">
          <span class="price"><?php echo money($o['price_cents']); ?></span>
          <form method="POST" action="index.php?action=add-to-order" class="qty-add">
            <input type="hidden" name="offer_id" value="<?php echo (int)$o['id']; ?>">
            <input type="number" name="qty" value="1" min="1" max="50">
            <button class="add-btn" type="submit">Order</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
</body></html>
