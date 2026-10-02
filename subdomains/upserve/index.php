<?php
// Hacker Café — Online ordering
// A realistic online food-ordering site used for hands-on web-security practice.
// Students register real accounts, build a cart, save a payment method, and check out.
//
// The intended flaw (mirrors HackerOne #364843, Upserve OLO): the checkout endpoint
// receives a fully client-assembled order (items with their own price/quantity/total,
// taxes, tip, delivery fee, and a grand total) and only verifies that those numbers
// are INTERNALLY CONSISTENT with each other. It never re-derives the charge from its
// own menu catalog. A shopper who edits the outgoing request can therefore: add a
// line with a negative quantity to cancel out real items, rewrite a line's unit price
// arbitrarily (as long as total = price * quantity still holds), or drop the delivery
// fee out of the running total — all while the request stays "balanced" and is
// accepted and charged as submitted.

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

$db->query("CREATE TABLE IF NOT EXISTS upserve_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT '',
    line1 VARCHAR(150) DEFAULT '',
    city VARCHAR(80) DEFAULT '',
    state VARCHAR(10) DEFAULT '',
    zip VARCHAR(15) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS upserve_menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_uuid VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(255) DEFAULT '',
    price_cents INT NOT NULL,
    category VARCHAR(60) DEFAULT 'Mains',
    emoji VARCHAR(10) DEFAULT '🍽️',
    image_url VARCHAR(255) DEFAULT ''
)") or die('init error');
$db->query("ALTER TABLE upserve_menu_items ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) DEFAULT ''");

$db->query("CREATE TABLE IF NOT EXISTS upserve_cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    card_uuid VARCHAR(64) NOT NULL UNIQUE,
    brand VARCHAR(20) NOT NULL,
    last4 VARCHAR(4) NOT NULL,
    holder VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS upserve_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_uuid VARCHAR(64) NOT NULL,
    confirmation_code VARCHAR(60) NOT NULL,
    submission_id VARCHAR(64) NOT NULL,
    store_pretty_url VARCHAR(80) DEFAULT 'hacker-cafe-providence',
    fulfillment_type VARCHAR(20) NOT NULL DEFAULT 'pickup',
    address_json TEXT,
    items_json TEXT NOT NULL,
    taxes_cents INT NOT NULL DEFAULT 0,
    tip_cents INT NOT NULL DEFAULT 0,
    delivery_fee_cents INT NOT NULL DEFAULT 0,
    total_cents INT NOT NULL,
    card_uuid VARCHAR(64) DEFAULT '',
    time_placed VARCHAR(40) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($cents) { return '$' . number_format(((int)$cents) / 100, 2); }
function uuidv4() {
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
    $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}
function base_url() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path   = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    return "{$scheme}://{$host}{$path}";
}

// ── Seed menu (authoritative catalog prices) ─────────────────────────────────────
$seed = [
    ['ChickenBurger', 'House-ground chicken patty, pickles, chipotle mayo', 1200, 'Mains', '🍔', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=500&fit=crop&q=80'],
    ['BreadPudding', 'Warm brioche bread pudding, bourbon caramel', 900, 'Desserts', '🍮', 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=500&fit=crop&q=80'],
    ['Loaded Fries', 'Crispy fries, cheddar, bacon, scallions', 450, 'Sides', '🍟', 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?w=500&fit=crop&q=80'],
    ['Chocolate Milkshake', 'Hand-spun, whipped cream, cherry', 550, 'Drinks', '🥤', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=500&fit=crop&q=80'],
    ['Caesar Salad', 'Romaine, parmesan, garlic croutons', 850, 'Mains', '🥗', 'https://images.unsplash.com/photo-1550304943-4f24f54ddde9?w=500&fit=crop&q=80'],
    ['Veggie Wrap', 'Grilled vegetables, hummus, feta, spinach tortilla', 750, 'Mains', '🌯', 'https://images.unsplash.com/photo-1626700051175-6818013e1d4f?w=500&fit=crop&q=80'],
    ['NY Cheesecake', 'Classic New York style, berry compote', 650, 'Desserts', '🍰', 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?w=500&fit=crop&q=80'],
    ['Iced Tea', 'Fresh brewed, unsweetened or sweet', 300, 'Drinks', '🧊', 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=500&fit=crop&q=80'],
    ['Buffalo Wings (8pc)', 'Crispy wings, classic buffalo, blue cheese', 1100, 'Mains', '🍗', 'https://images.unsplash.com/photo-1608039755401-742074f0548d?w=500&fit=crop&q=80'],
    ['Margherita Pizza', 'Wood-fired, san marzano, fresh mozzarella', 1400, 'Mains', '🍕', 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=500&fit=crop&q=80'],
];
$menuCheck = $db->query("SELECT COUNT(*) AS c FROM upserve_menu_items")->fetch_assoc();
if ((int)$menuCheck['c'] === 0) {
    $st = $db->prepare("INSERT INTO upserve_menu_items (item_uuid, name, description, price_cents, category, emoji, image_url) VALUES (?,?,?,?,?,?,?)");
    foreach ($seed as $s) {
        $uuid = uuidv4();
        $st->bind_param('sssisss', $uuid, $s[0], $s[1], $s[2], $s[3], $s[4], $s[5]);
        $st->execute();
    }
} else {
    // Backfill image_url for rows seeded before photos were added.
    $st = $db->prepare("UPDATE upserve_menu_items SET image_url=? WHERE name=? AND (image_url IS NULL OR image_url='')");
    foreach ($seed as $s) {
        $st->bind_param('ss', $s[5], $s[0]);
        $st->execute();
    }
}

function current_user($db) {
    if (empty($_SESSION['uid'])) return null;
    $st = $db->prepare("SELECT * FROM upserve_users WHERE id=?");
    $st->bind_param('i', $_SESSION['uid']);
    $st->execute();
    return $st->get_result()->fetch_assoc();
}
function menu_items($db) {
    $out = [];
    $r = $db->query("SELECT * FROM upserve_menu_items ORDER BY category, name");
    while ($row = $r->fetch_assoc()) $out[] = $row;
    return $out;
}
function menu_item_by_uuid($db, $uuid) {
    $st = $db->prepare("SELECT * FROM upserve_menu_items WHERE item_uuid=?");
    $st->bind_param('s', $uuid);
    $st->execute();
    return $st->get_result()->fetch_assoc();
}
function menu_item_by_id($db, $id) {
    $st = $db->prepare("SELECT * FROM upserve_menu_items WHERE id=?");
    $st->bind_param('i', $id);
    $st->execute();
    return $st->get_result()->fetch_assoc();
}
function user_cards($db, $userId) {
    $st = $db->prepare("SELECT * FROM upserve_cards WHERE user_id=? ORDER BY created_at DESC");
    $st->bind_param('i', $userId);
    $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function card_belongs_to($db, $cardUuid, $userId) {
    $st = $db->prepare("SELECT id FROM upserve_cards WHERE card_uuid=? AND user_id=?");
    $st->bind_param('si', $cardUuid, $userId);
    $st->execute();
    return (bool)$st->get_result()->fetch_row();
}
function user_orders($db, $userId) {
    $st = $db->prepare("SELECT * FROM upserve_orders WHERE user_id=? ORDER BY id DESC");
    $st->bind_param('i', $userId);
    $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function get_order($db, $id, $userId) {
    $st = $db->prepare("SELECT * FROM upserve_orders WHERE id=? AND user_id=?");
    $st->bind_param('ii', $id, $userId);
    $st->execute();
    return $st->get_result()->fetch_assoc();
}
function first_name($name) { $p = preg_split('/\s+/', trim($name)); return $p[0] ?: $name; }
function avatar_letter($name) { return strtoupper(mb_substr(trim($name), 0, 1) ?: 'U'); }

// ── Cart helpers (server-side session cart, used for the honest UI path) ─────────
function cart() { if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = []; return $_SESSION['cart']; }
function cart_count() { $c = cart(); return array_sum($c); }

$action = $_GET['action'] ?? 'menu';
$me = current_user($db);

// ── Auth ───────────────────────────────────────────────────────────────────────
if ($action === 'logout') { session_destroy(); header('Location: index.php?action=login'); exit; }

$authError = '';
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first = trim($_POST['first_name'] ?? '');
        $last  = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $authError = 'Enter your name, a valid email, and a password of at least 6 characters.';
        } else {
            $st = $db->prepare("SELECT id FROM upserve_users WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            if ($st->get_result()->fetch_row()) {
                $authError = 'An account with that email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $st = $db->prepare("INSERT INTO upserve_users (first_name,last_name,email,password_hash,phone) VALUES (?,?,?,?,?)");
                $st->bind_param('sssss', $first, $last, $email, $hash, $phone);
                $st->execute();
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
        $st = $db->prepare("SELECT * FROM upserve_users WHERE email=?");
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

// ── Cart mutation endpoints (server-authoritative, used by the normal UI) ────────
if ($action === 'add-to-cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $iid = (int)($_POST['item_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if (menu_item_by_id($db, $iid)) {
        $c = cart(); $c[$iid] = ($c[$iid] ?? 0) + $qty; $_SESSION['cart'] = $c;
    }
    header('Location: index.php?action=menu&added=1'); exit;
}
if ($action === 'update-cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $c = cart();
    foreach ($_POST['qty'] ?? [] as $iid => $qty) {
        $iid = (int)$iid; $qty = max(0, (int)$qty);
        if ($qty === 0) unset($c[$iid]); else $c[$iid] = $qty;
    }
    $_SESSION['cart'] = $c;
    header('Location: index.php?action=cart'); exit;
}
if ($action === 'remove-from-cart') {
    $iid = (int)($_GET['id'] ?? 0);
    $c = cart(); unset($c[$iid]); $_SESSION['cart'] = $c;
    header('Location: index.php?action=cart'); exit;
}

// ── Add a payment method ─────────────────────────────────────────────────────────
if ($action === 'add-card' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $holder = trim($_POST['holder'] ?? '');
    $number = preg_replace('/\D/', '', $_POST['number'] ?? '');
    $brand  = 'Visa';
    if (preg_match('/^5/', $number)) $brand = 'Mastercard';
    elseif (preg_match('/^3/', $number)) $brand = 'Amex';
    $last4 = substr($number, -4) ?: '0000';
    if ($holder !== '' && strlen($number) >= 12) {
        $cardUuid = uuidv4();
        $st = $db->prepare("INSERT INTO upserve_cards (user_id, card_uuid, brand, last4, holder) VALUES (?,?,?,?,?)");
        $st->bind_param('issss', $myId, $cardUuid, $brand, $last4, $holder);
        $st->execute();
    }
    header('Location: index.php?action=cards'); exit;
}

// ── VULNERABLE ENDPOINT — place an order ─────────────────────────────────────────
// The client assembles the FULL order (items with their own price/quantity/total,
// taxes, tip, delivery fee, grand total) and POSTs it here. The handler below only
// checks that these numbers agree WITH EACH OTHER — it never looks up the menu's
// real prices, never rejects a negative quantity, and never requires a delivery fee
// to be present. Whatever balances is accepted and charged.
if ($action === 'place-order') {
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method not allowed']); exit; }

    $body = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body) || !isset($body['order']['charges']['items'])) {
        http_response_code(400); echo json_encode(['error' => 'Malformed order payload.']); exit;
    }

    $cardUuid = $body['card_uuid'] ?? '';
    if (!$cardUuid || !card_belongs_to($db, $cardUuid, $myId)) {
        http_response_code(403); echo json_encode(['error' => 'Invalid or unrecognized payment method.']); exit;
    }

    $charges = $body['order']['charges'];
    $items   = $charges['items'];
    if (!is_array($items) || count($items) === 0) {
        http_response_code(422); echo json_encode(['error' => 'Order must contain at least one item.']); exit;
    }

    // Each item must reference a real menu item... but its price/quantity/total are
    // whatever the client says (THE BUG: no comparison against upserve_menu_items.price_cents,
    // and no rule that quantity must be positive).
    $itemsSum = 0;
    foreach ($items as $it) {
        $iid = $it['item_id'] ?? '';
        $mi  = menu_item_by_uuid($db, $iid);
        if (!$mi) { http_response_code(422); echo json_encode(['error' => 'Order references an unknown item.']); exit; }

        $price = (int)($it['price'] ?? 0);
        $qty   = (int)($it['quantity'] ?? 0);
        $total = (int)($it['total'] ?? 0);
        if ($total !== $price * $qty) {
            http_response_code(422); echo json_encode(['error' => 'Item total does not match price × quantity.']); exit;
        }
        $itemsSum += $total;
    }

    $taxes        = (int)($charges['taxes'] ?? 0);
    $tipAmount    = (int)($charges['tip']['amount'] ?? 0);
    $deliveryFee  = (int)($charges['delivery_fee'] ?? 0);
    $chargesTotal = (int)($charges['total'] ?? 0);
    $orderTotal   = (int)($body['order_total'] ?? 0);
    $paymentsTotal = (int)($body['order']['payments']['total'] ?? 0);

    $computed = $itemsSum + $taxes + $tipAmount + $deliveryFee;
    if ($computed !== $chargesTotal) {
        http_response_code(422); echo json_encode(['error' => 'Order total does not match submitted charges.']); exit;
    }
    if ($chargesTotal !== $orderTotal) {
        http_response_code(422); echo json_encode(['error' => 'Order total does not match top-level total.']); exit;
    }
    if ($paymentsTotal !== $orderTotal) {
        http_response_code(422); echo json_encode(['error' => 'Payment total does not match order total.']); exit;
    }
    // ------------------------------------------------------------------------

    $orderUuid  = $body['order']['id'] ?? uuidv4();
    $confCode   = $body['order']['confirmation_code'] ?? ('hacker-cafe-' . random_int(10000, 99999));
    $submission = $body['submission_id'] ?? uuidv4();
    $fulfType   = $body['order']['fulfillment_info']['type'] ?? 'pickup';
    $addressJson = json_encode($body['order']['fulfillment_info']['delivery_info']['address'] ?? [
        'address_line1' => $body['line1'] ?? '', 'city' => $body['city'] ?? '', 'state' => $body['state'] ?? '', 'zip_code' => $body['zip'] ?? '',
    ]);
    $itemsJson  = json_encode($items);
    $timePlaced = $body['order']['time_placed'] ?? gmdate('Y-m-d\TH:i:s.000\Z');

    $st = $db->prepare("INSERT INTO upserve_orders
        (user_id, order_uuid, confirmation_code, submission_id, fulfillment_type, address_json, items_json, taxes_cents, tip_cents, delivery_fee_cents, total_cents, card_uuid, time_placed)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->bind_param('issssssiiiiss', $myId, $orderUuid, $confCode, $submission, $fulfType, $addressJson, $itemsJson, $taxes, $tipAmount, $deliveryFee, $orderTotal, $cardUuid, $timePlaced);
    $st->execute();
    $newId = $db->insert_id;

    $_SESSION['cart'] = []; // clear cart on success

    echo json_encode([
        'status' => 'accepted',
        'order_id' => $newId,
        'id' => $orderUuid,
        'confirmation_code' => $confCode,
        'submission_id' => $submission,
        'time_placed' => $timePlaced,
        'total' => $orderTotal,
    ]);
    exit;
}

$items = menu_items($db);
$itemsById = [];
foreach ($items as $it) $itemsById[(int)$it['id']] = $it;
$cartArr = cart();
$cartCount = cart_count();

// =========================================================================
//  RENDER
// =========================================================================
function render_auth($mode, $error) {
    $isReg = ($mode === 'register');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isReg ? 'Sign up' : 'Sign in'; ?> — Hacker Café</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#fdf8f1;color:#2a1b12;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.auth{width:400px;max-width:92vw;}
.logo-row{display:flex;align-items:center;gap:0.6rem;justify-content:center;font-family:'Fraunces',serif;font-weight:700;font-size:1.5rem;margin-bottom:1.75rem;color:#7c1d1d;}
.logo-row .mark{width:34px;height:34px;border-radius:9px;background:#7c1d1d;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;}
.card{background:#fff;border:1px solid #ecdfcd;border-radius:16px;padding:2rem;box-shadow:0 2px 10px rgba(60,30,10,0.05);}
.card h1{font-size:1.3rem;margin:0 0 0.35rem;font-family:'Fraunces',serif;}
.card p.s{color:#8a7360;font-size:0.88rem;margin:0 0 1.5rem;}
label{display:block;font-size:0.8rem;color:#6b5847;margin:0.9rem 0 0.35rem;font-weight:500;}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;}
input{width:100%;background:#fdf8f1;border:1px solid #e6d6bf;color:#2a1b12;border-radius:9px;padding:0.7rem 0.8rem;font-size:0.92rem;}
input:focus{outline:none;border-color:#b5451f;}
.btn-primary{width:100%;margin-top:1.4rem;background:#b5451f;border:none;color:#fff;padding:0.75rem;border-radius:9px;font-size:0.95rem;font-weight:600;cursor:pointer;}
.btn-primary:hover{background:#953a19;}
.alt{text-align:center;margin-top:1.25rem;font-size:0.86rem;color:#8a7360;}
.alt a{color:#b5451f;text-decoration:none;font-weight:600;}
.err{background:#fdecec;border:1px solid #f3b9b9;color:#a12a2a;padding:0.7rem 0.9rem;border-radius:9px;font-size:0.85rem;margin-bottom:1rem;}
</style></head><body>
<div class="auth">
  <div class="logo-row"><span class="mark">HC</span> Hacker Café</div>
  <div class="card">
    <h1><?php echo $isReg ? 'Create your account' : 'Welcome back'; ?></h1>
    <p class="s"><?php echo $isReg ? 'Order ahead for pickup or delivery.' : 'Sign in to order.'; ?></p>
    <?php if ($error): ?><div class="err"><?php echo esc($error); ?></div><?php endif; ?>
    <form method="POST" action="index.php?action=<?php echo $isReg ? 'register' : 'login'; ?><?php echo isset($_GET['next']) ? '&next=' . esc($_GET['next']) : ''; ?>">
      <?php if ($isReg): ?>
        <div class="two-col">
          <div><label>First name</label><input name="first_name" required></div>
          <div><label>Last name</label><input name="last_name" required></div>
        </div>
        <label>Phone</label><input name="phone" placeholder="555-555-5555">
      <?php endif; ?>
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
<title><?php echo esc($title); ?> — Hacker Café</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Fraunces:wght@600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#fdf8f1;color:#2a1b12;font-family:'Inter',sans-serif;}
a{color:inherit;}
h1,h2{font-family:'Fraunces',serif;}
::-webkit-scrollbar{width:8px;}
::-webkit-scrollbar-thumb{background:#e6d6bf;border-radius:4px;}
.topbar{position:sticky;top:0;background:#fff;border-bottom:1px solid #ecdfcd;z-index:20;}
.topbar-in{max-width:1080px;margin:0 auto;padding:0.85rem 1.5rem;display:flex;align-items:center;justify-content:space-between;}
.brand{display:flex;align-items:center;gap:0.55rem;font-family:'Fraunces',serif;font-weight:700;font-size:1.2rem;color:#7c1d1d;text-decoration:none;}
.brand .mark{width:30px;height:30px;border-radius:8px;background:#7c1d1d;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.9rem;}
.nav{display:flex;gap:1.4rem;align-items:center;}
.nav a{font-size:0.9rem;color:#5b4635;text-decoration:none;font-weight:500;}
.nav a.active,.nav a:hover{color:#7c1d1d;}
.nav .cart-link{position:relative;}
.nav .cart-badge{position:absolute;top:-8px;right:-14px;background:#b5451f;color:#fff;font-size:0.65rem;font-weight:700;border-radius:10px;padding:0.05rem 0.4rem;}
.acct{display:flex;align-items:center;gap:0.6rem;}
.acct .av{width:30px;height:30px;border-radius:50%;background:#b5451f;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.82rem;font-weight:700;}
.btn{background:#fff;border:1px solid #e6d6bf;color:#3a2a1c;padding:0.4rem 0.9rem;border-radius:8px;font-size:0.84rem;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;}
.btn:hover{background:#fbf1e2;}
.btn-primary{background:#b5451f;border:none;color:#fff;}
.btn-primary:hover{background:#953a19;}
.wrap{max-width:1080px;margin:0 auto;padding:2rem 1.5rem;}
.hero{background:linear-gradient(135deg,#7c1d1d,#b5451f);color:#fff;padding:2.5rem 1.5rem;border-radius:0 0 22px 22px;margin-bottom:1rem;}
.hero-in{max-width:1080px;margin:0 auto;}
.hero h1{font-size:2rem;margin:0 0 0.4rem;}
.hero p{opacity:0.9;margin:0;font-size:0.95rem;}
.cat-title{font-size:1.25rem;margin:1.75rem 0 0.9rem;}
.menu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:1rem;}
.mcard{background:#fff;border:1px solid #ecdfcd;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;}
.mcard .thumb{height:130px;display:flex;align-items:center;justify-content:center;font-size:2.4rem;background:#fbf1e2;overflow:hidden;}
.mcard .thumb img{width:100%;height:100%;object-fit:cover;display:block;}
.cart-line .ic{width:44px;height:44px;border-radius:10px;object-fit:cover;flex-shrink:0;}
.mcard .body{padding:0.9rem 1rem;flex:1;display:flex;flex-direction:column;}
.mcard .nm{font-weight:700;font-size:0.98rem;}
.mcard .dsc{font-size:0.78rem;color:#8a7360;margin-top:0.2rem;flex:1;}
.mcard .foot{display:flex;align-items:center;justify-content:space-between;margin-top:0.75rem;}
.mcard .price{font-weight:700;color:#7c1d1d;}
.qty-add{display:flex;gap:0.35rem;align-items:center;}
.qty-add input{width:44px;text-align:center;border:1px solid #e6d6bf;border-radius:7px;padding:0.3rem;font-size:0.85rem;}
.add-btn{background:#b5451f;border:none;color:#fff;border-radius:7px;padding:0.35rem 0.7rem;font-size:0.8rem;cursor:pointer;font-weight:600;}
.add-btn:hover{background:#953a19;}
.notice{background:#eaf6ec;border:1px solid #b7e0bd;color:#1e7a34;padding:0.75rem 1rem;border-radius:10px;font-size:0.85rem;margin-bottom:1.25rem;}
.warn{background:#fdecec;border:1px solid #f3b9b9;color:#a12a2a;padding:0.75rem 1rem;border-radius:10px;font-size:0.85rem;margin-bottom:1.25rem;}
.empty{color:#8a7360;font-size:0.9rem;padding:2.5rem 0;text-align:center;}
/* Cart / checkout */
.cart-line{display:flex;align-items:center;gap:1rem;background:#fff;border:1px solid #ecdfcd;border-radius:12px;padding:0.85rem 1rem;margin-bottom:0.6rem;}
.cart-line .ic{font-size:1.6rem;}
.cart-line .nm{font-weight:600;flex:1;}
.cart-line .unit{color:#8a7360;font-size:0.8rem;}
.cart-line input{width:56px;text-align:center;border:1px solid #e6d6bf;border-radius:7px;padding:0.3rem;}
.cart-line .lt{font-weight:700;width:80px;text-align:right;}
.cart-line .rm{color:#b5451f;font-size:0.8rem;text-decoration:none;}
.summary{background:#fff;border:1px solid #ecdfcd;border-radius:14px;padding:1.25rem;position:sticky;top:90px;}
.summary .row{display:flex;justify-content:space-between;font-size:0.88rem;margin-bottom:0.5rem;color:#5b4635;}
.summary .row.total{font-weight:700;color:#2a1b12;font-size:1.05rem;border-top:1px solid #ecdfcd;padding-top:0.6rem;margin-top:0.6rem;}
.layout{display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;}
@media(max-width:820px){.layout{grid-template-columns:1fr;}}
.field{margin-bottom:0.9rem;}
.field label{display:block;font-size:0.8rem;color:#6b5847;margin-bottom:0.3rem;font-weight:500;}
.field input,.field select{width:100%;background:#fdf8f1;border:1px solid #e6d6bf;border-radius:9px;padding:0.6rem 0.75rem;font-size:0.9rem;}
.toggle-row{display:flex;gap:0.6rem;margin-bottom:1rem;}
.toggle-opt{flex:1;text-align:center;padding:0.6rem;border:1px solid #e6d6bf;border-radius:9px;cursor:pointer;font-size:0.88rem;font-weight:600;color:#5b4635;}
.toggle-opt.sel{background:#7c1d1d;color:#fff;border-color:#7c1d1d;}
.tip-row{display:flex;gap:0.5rem;margin-bottom:1rem;}
.tip-opt{flex:1;text-align:center;padding:0.5rem;border:1px solid #e6d6bf;border-radius:9px;cursor:pointer;font-size:0.85rem;font-weight:600;}
.tip-opt.sel{background:#b5451f;color:#fff;border-color:#b5451f;}
.card-row{display:flex;align-items:center;gap:0.7rem;background:#fff;border:1px solid #ecdfcd;border-radius:12px;padding:0.85rem 1rem;margin-bottom:0.6rem;}
.card-row .cbrand{width:42px;height:28px;border-radius:5px;background:#2a1b12;color:#fff;font-size:0.65rem;display:flex;align-items:center;justify-content:center;font-weight:700;}
.card-row input[type=radio]{margin-right:0.5rem;}
.receipt{background:#fff;border:1px solid #ecdfcd;border-radius:14px;padding:1.5rem;}
.receipt .conf{font-size:0.8rem;color:#8a7360;}
.receipt table{width:100%;border-collapse:collapse;margin-top:1rem;}
.receipt td{padding:0.4rem 0;font-size:0.88rem;border-bottom:1px solid #f3ead9;}
.receipt td.r{text-align:right;}
.order-card{background:#fff;border:1px solid #ecdfcd;border-radius:12px;padding:1rem 1.25rem;margin-bottom:0.75rem;}
.order-card .hd{display:flex;justify-content:space-between;align-items:center;}
.order-card .hd .cc{font-weight:700;}
.order-card .hd .amt{font-weight:700;color:#7c1d1d;}
.order-card .meta{font-size:0.78rem;color:#8a7360;margin-top:0.2rem;}
</style></head><body>
<?php
}

function render_topbar($active, $me, $cartCount) {
?>
<div class="topbar"><div class="topbar-in">
  <a href="index.php?action=menu" class="brand"><span class="mark">HC</span> Hacker Café</a>
  <div class="nav">
    <a href="index.php?action=menu" class="<?php echo $active==='menu'?'active':''; ?>">Menu</a>
    <a href="index.php?action=orders" class="<?php echo $active==='orders'?'active':''; ?>">Orders</a>
    <a href="index.php?action=cards" class="<?php echo $active==='cards'?'active':''; ?>">Payment methods</a>
    <a href="index.php?action=cart" class="cart-link <?php echo $active==='cart'?'active':''; ?>">Cart<?php if ($cartCount>0): ?><span class="cart-badge"><?php echo (int)$cartCount; ?></span><?php endif; ?></a>
  </div>
  <div class="acct">
    <span class="av"><?php echo esc(avatar_letter($me['first_name'])); ?></span>
    <span style="font-size:0.85rem;"><?php echo esc(first_name($me['first_name'])); ?></span>
    <a href="index.php?action=logout" class="btn">Sign out</a>
  </div>
</div></div>
<?php
}

// =========================================================================
//  PAGES
// =========================================================================

if ($action === 'cards') {
    $cards = user_cards($db, $myId);
    render_head('Payment methods'); render_topbar('cards', $me, $cartCount);
    ?>
    <div class="wrap" style="max-width:560px;">
      <h1 style="font-size:1.4rem;">Payment methods</h1>
      <p style="color:#8a7360;font-size:0.9rem;margin-top:-0.5rem;">Add a card to check out. This is a demo — no real card is charged.</p>
      <?php if (empty($cards)): ?><div class="warn">You don't have a saved payment method yet.</div><?php endif; ?>
      <?php foreach ($cards as $c): ?>
        <div class="card-row"><span class="cbrand"><?php echo esc(strtoupper(substr($c['brand'],0,4))); ?></span>
          <div>•••• •••• •••• <?php echo esc($c['last4']); ?><div style="font-size:0.75rem;color:#8a7360;"><?php echo esc($c['holder']); ?></div></div>
        </div>
      <?php endforeach; ?>
      <h2 style="font-size:1.05rem;margin-top:1.5rem;">Add a card</h2>
      <form method="POST" action="index.php?action=add-card">
        <div class="field"><label>Name on card</label><input name="holder" required></div>
        <div class="field"><label>Card number</label><input name="number" inputmode="numeric" placeholder="4242 4242 4242 4242" required></div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
          <div class="field"><label>Expiry</label><input name="exp" placeholder="MM/YY"></div>
          <div class="field"><label>CVC</label><input name="cvc" placeholder="123"></div>
        </div>
        <button class="btn btn-primary" style="width:100%;justify-content:center;" type="submit">Save card</button>
      </form>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'cart') {
    render_head('Your cart'); render_topbar('cart', $me, $cartCount);
    global $itemsById;
    $lines = [];
    $subtotal = 0;
    foreach ($cartArr as $iid => $qty) {
        if (!isset($itemsById[$iid])) continue;
        $it = $itemsById[$iid];
        $lineTotal = $it['price_cents'] * $qty;
        $subtotal += $lineTotal;
        $lines[] = ['item' => $it, 'qty' => $qty, 'total' => $lineTotal];
    }
    ?>
    <div class="wrap">
      <h1 style="font-size:1.4rem;">Your cart</h1>
      <?php if (empty($lines)): ?>
        <div class="empty">Your cart is empty. <a href="index.php?action=menu" style="color:#b5451f;">Browse the menu</a>.</div>
      <?php else: ?>
      <form method="POST" action="index.php?action=update-cart">
        <?php foreach ($lines as $l): $it = $l['item']; ?>
        <div class="cart-line">
          <img class="ic" src="<?php echo esc($it['image_url']); ?>" alt="<?php echo esc($it['name']); ?>">
          <div class="nm"><?php echo esc($it['name']); ?><div class="unit"><?php echo money($it['price_cents']); ?> each</div></div>
          <input type="number" name="qty[<?php echo (int)$it['id']; ?>]" value="<?php echo (int)$l['qty']; ?>" min="1" max="20">
          <div class="lt"><?php echo money($l['total']); ?></div>
          <a class="rm" href="index.php?action=remove-from-cart&id=<?php echo (int)$it['id']; ?>">Remove</a>
        </div>
        <?php endforeach; ?>
        <div style="display:flex;gap:0.6rem;margin-top:1rem;">
          <button class="btn" type="submit">Update quantities</button>
          <a class="btn btn-primary" href="index.php?action=checkout">Proceed to checkout →</a>
        </div>
      </form>
      <div style="margin-top:1rem;font-size:0.9rem;color:#5b4635;">Subtotal: <strong><?php echo money($subtotal); ?></strong> (tax &amp; fees calculated at checkout)</div>
      <?php endif; ?>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'checkout') {
    global $itemsById;
    $cards = user_cards($db, $myId);
    $lines = [];
    $subtotal = 0;
    foreach ($cartArr as $iid => $qty) {
        if (!isset($itemsById[$iid])) continue;
        $it = $itemsById[$iid];
        $lineTotal = $it['price_cents'] * $qty;
        $subtotal += $lineTotal;
        $lines[] = ['item' => $it, 'qty' => $qty, 'total' => $lineTotal];
    }
    if (empty($lines) || empty($cards)) {
        render_head('Checkout'); render_topbar('cart', $me, $cartCount);
        echo '<div class="wrap">';
        if (empty($lines)) echo '<div class="warn">Your cart is empty. <a href="index.php?action=menu">Browse the menu</a>.</div>';
        if (empty($cards)) echo '<div class="warn">Add a payment method before checking out. <a href="index.php?action=cards">Add a card</a>.</div>';
        echo '</div></body></html>';
        exit;
    }
    $TAX_RATE = 0.0825;
    $DELIVERY_FEE = 399;
    render_head('Checkout'); render_topbar('cart', $me, $cartCount);
    ?>
    <div class="wrap">
      <h1 style="font-size:1.4rem;">Checkout</h1>
      <div class="layout">
        <div>
          <div class="toggle-row">
            <div class="toggle-opt sel" id="opt-pickup" onclick="setFulfillment('pickup')">Pickup</div>
            <div class="toggle-opt" id="opt-delivery" onclick="setFulfillment('delivery')">Delivery (+<?php echo money($DELIVERY_FEE); ?>)</div>
          </div>
          <div id="address-fields">
            <div class="field"><label>Address</label><input id="f-line1" value="<?php echo esc($me['line1']); ?>"></div>
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:0.6rem;">
              <div class="field"><label>City</label><input id="f-city" value="<?php echo esc($me['city']); ?>"></div>
              <div class="field"><label>State</label><input id="f-state" value="<?php echo esc($me['state']); ?>"></div>
              <div class="field"><label>Zip</label><input id="f-zip" value="<?php echo esc($me['zip']); ?>"></div>
            </div>
          </div>
          <h2 style="font-size:1rem;margin-top:1.25rem;">Tip</h2>
          <div class="tip-row">
            <div class="tip-opt" onclick="setTip(0)">No tip</div>
            <div class="tip-opt sel" onclick="setTip(0.15)">15%</div>
            <div class="tip-opt" onclick="setTip(0.20)">20%</div>
            <div class="tip-opt" onclick="setTip(0.25)">25%</div>
          </div>
          <h2 style="font-size:1rem;margin-top:1.25rem;">Payment</h2>
          <?php foreach ($cards as $i => $c): ?>
          <label class="card-row" style="cursor:pointer;">
            <input type="radio" name="card" value="<?php echo esc($c['card_uuid']); ?>" <?php echo $i===0?'checked':''; ?> onchange="selectedCard='<?php echo esc($c['card_uuid']); ?>'">
            <span class="cbrand"><?php echo esc(strtoupper(substr($c['brand'],0,4))); ?></span>
            <div>•••• <?php echo esc($c['last4']); ?></div>
          </label>
          <?php endforeach; ?>
        </div>
        <div class="summary">
          <div style="font-weight:700;margin-bottom:0.75rem;">Order summary</div>
          <?php foreach ($lines as $l): ?>
            <div class="row"><span><?php echo (int)$l['qty']; ?>× <?php echo esc($l['item']['name']); ?></span><span><?php echo money($l['total']); ?></span></div>
          <?php endforeach; ?>
          <div class="row"><span>Subtotal</span><span id="s-subtotal"><?php echo money($subtotal); ?></span></div>
          <div class="row"><span>Tax</span><span id="s-tax"></span></div>
          <div class="row" id="row-delivery" style="display:none;"><span>Delivery fee</span><span id="s-delivery"></span></div>
          <div class="row"><span>Tip</span><span id="s-tip"></span></div>
          <div class="row total"><span>Total</span><span id="s-total"></span></div>
          <button class="btn btn-primary" style="width:100%;justify-content:center;margin-top:1rem;" onclick="placeOrder()">Place order</button>
          <div id="orderError" style="display:none;color:#a12a2a;font-size:0.82rem;margin-top:0.6rem;"></div>
        </div>
      </div>
    </div>
    <script>
    const SUBTOTAL = <?php echo (int)$subtotal; ?>;
    const TAX_RATE = <?php echo $TAX_RATE; ?>;
    const DELIVERY_FEE = <?php echo (int)$DELIVERY_FEE; ?>;
    const CART_LINES = <?php echo json_encode(array_map(function($l){
        return ['item_id'=>$l['item']['item_uuid'],'name'=>$l['item']['name'],'price'=>(int)$l['item']['price_cents'],'quantity'=>(int)$l['qty']];
    }, $lines)); ?>;
    const ME = <?php echo json_encode(['first_name'=>$me['first_name'],'last_name'=>$me['last_name'],'email'=>$me['email'],'phone'=>$me['phone']]); ?>;

    let fulfillment = 'pickup';
    let tipRate = 0.15;
    let selectedCard = document.querySelector('input[name=card]:checked')?.value || '';

    function setFulfillment(type){
      fulfillment = type;
      document.getElementById('opt-pickup').classList.toggle('sel', type==='pickup');
      document.getElementById('opt-delivery').classList.toggle('sel', type==='delivery');
      document.getElementById('row-delivery').style.display = (type==='delivery') ? 'flex' : 'none';
      recalc();
    }
    function setTip(rate){
      tipRate = rate;
      document.querySelectorAll('.tip-opt').forEach(el=>el.classList.remove('sel'));
      event.currentTarget.classList.add('sel');
      recalc();
    }
    function recalc(){
      const tax = Math.round(SUBTOTAL * TAX_RATE);
      const delivery = fulfillment === 'delivery' ? DELIVERY_FEE : 0;
      const tip = Math.round(SUBTOTAL * tipRate);
      const total = SUBTOTAL + tax + delivery + tip;
      document.getElementById('s-tax').textContent = '$' + (tax/100).toFixed(2);
      document.getElementById('s-delivery').textContent = '$' + (delivery/100).toFixed(2);
      document.getElementById('s-tip').textContent = '$' + (tip/100).toFixed(2);
      document.getElementById('s-total').textContent = '$' + (total/100).toFixed(2);
      return {tax, delivery, tip, total};
    }
    recalc();

    function uuidv4(){
      return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c=>{
        const r = Math.random()*16|0, v = c==='x' ? r : (r&0x3|0x8);
        return v.toString(16);
      });
    }

    function placeOrder(){
      const {tax, delivery, tip, total} = recalc();
      const items = CART_LINES.map(l => ({
        item_id: l.item_id, name: l.name, price: l.price, quantity: l.quantity,
        instructions: '', total: l.price * l.quantity, modifiers: [], sides: []
      }));
      const orderId = uuidv4();
      const confCode = 'hacker-cafe-' + Math.floor(10000 + Math.random()*89999);
      const submissionId = uuidv4();
      const timePlaced = new Date().toISOString();

      const payload = {
        card_uuid: selectedCard,
        first_name: ME.first_name, last_name: ME.last_name, email: ME.email, phone_number: ME.phone,
        line1: document.getElementById('f-line1').value,
        city: document.getElementById('f-city').value,
        state: document.getElementById('f-state').value,
        zip: document.getElementById('f-zip').value,
        store_pretty_url: 'hacker-cafe-providence',
        text_alerts: false,
        order: {
          id: orderId,
          time_placed: timePlaced,
          confirmation_code: confCode,
          charges: { items: items, taxes: tax, tip: { amount: tip }, delivery_fee: delivery, total: total },
          fulfillment_info: {
            type: fulfillment, instructions: '',
            customer: { email: ME.email, first_name: ME.first_name, last_name: ME.last_name, phone: ME.phone },
            delivery_info: { address: { address_line1: document.getElementById('f-line1').value, address_line2: null, city: document.getElementById('f-city').value, country: 'US', state: document.getElementById('f-state').value, zip_code: document.getElementById('f-zip').value } }
          },
          payments: { payments: [{ amount: total, payment_type: 'CREDIT', tip_amount: tip }], total: total }
        },
        order_total: total,
        submission_id: submissionId
      };

      fetch('?action=place-order', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) })
        .then(r => r.json().then(d => ({ok: r.ok, d})))
        .then(({ok, d}) => {
          if (!ok) { document.getElementById('orderError').style.display='block'; document.getElementById('orderError').textContent = d.error || 'Order could not be placed.'; return; }
          window.location.href = 'index.php?action=order&id=' + d.order_id;
        })
        .catch(() => { document.getElementById('orderError').style.display='block'; document.getElementById('orderError').textContent = 'Network error.'; });
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
      <div class="notice">Order placed! Your card ending in the payment method on file was charged <?php echo money($o['total_cents']); ?>.</div>
      <div class="receipt">
        <div style="font-weight:700;font-size:1.05rem;"><?php echo esc($o['confirmation_code']); ?></div>
        <div class="conf">Order ID <?php echo esc($o['order_uuid']); ?> · Placed <?php echo esc($o['time_placed']); ?> · <?php echo esc(ucfirst($o['fulfillment_type'])); ?></div>
        <table>
          <?php foreach ($itemsArr as $it): ?>
          <tr><td><?php echo (int)($it['quantity'] ?? 0); ?>× <?php echo esc($it['name'] ?? ''); ?> @ <?php echo money($it['price'] ?? 0); ?></td><td class="r"><?php echo money($it['total'] ?? 0); ?></td></tr>
          <?php endforeach; ?>
          <tr><td>Tax</td><td class="r"><?php echo money($o['taxes_cents']); ?></td></tr>
          <?php if ((int)$o['delivery_fee_cents'] > 0 || $o['fulfillment_type']==='delivery'): ?>
          <tr><td>Delivery fee</td><td class="r"><?php echo money($o['delivery_fee_cents']); ?></td></tr>
          <?php endif; ?>
          <tr><td>Tip</td><td class="r"><?php echo money($o['tip_cents']); ?></td></tr>
          <tr><td style="font-weight:700;">Total charged</td><td class="r" style="font-weight:700;"><?php echo money($o['total_cents']); ?></td></tr>
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
        <div class="empty">No orders yet. <a href="index.php?action=menu" style="color:#b5451f;">Order something delicious.</a></div>
      <?php else: foreach ($orders as $o):
        $itemsArr = json_decode($o['items_json'], true) ?: [];
        $itemCount = count($itemsArr);
      ?>
      <a href="index.php?action=order&id=<?php echo (int)$o['id']; ?>" style="text-decoration:none;color:inherit;">
      <div class="order-card">
        <div class="hd"><span class="cc"><?php echo esc($o['confirmation_code']); ?></span><span class="amt"><?php echo money($o['total_cents']); ?></span></div>
        <div class="meta"><?php echo (int)$itemCount; ?> item<?php echo $itemCount!==1?'s':''; ?> · <?php echo esc(ucfirst($o['fulfillment_type'])); ?> · <?php echo esc($o['time_placed']); ?></div>
      </div>
      </a>
      <?php endforeach; endif; ?>
    </div>
    </body></html>
    <?php
    exit;
}

// ── Default: Menu ────────────────────────────────────────────────────────────────
$added = isset($_GET['added']);
render_head('Menu'); render_topbar('menu', $me, $cartCount);
$byCategory = [];
foreach ($items as $it) { $byCategory[$it['category']][] = $it; }
?>
<div class="hero"><div class="hero-in">
  <h1>Hacker Café</h1>
  <p>Providence, RI · Order online for pickup or delivery</p>
</div></div>
<div class="wrap">
  <?php if ($added): ?><div class="notice">Added to your cart.</div><?php endif; ?>
  <?php foreach ($byCategory as $cat => $catItems): ?>
  <h2 class="cat-title"><?php echo esc($cat); ?></h2>
  <div class="menu-grid">
    <?php foreach ($catItems as $it): ?>
    <div class="mcard">
      <div class="thumb"><img src="<?php echo esc($it['image_url']); ?>" alt="<?php echo esc($it['name']); ?>" loading="lazy"></div>
      <div class="body">
        <div class="nm"><?php echo esc($it['name']); ?></div>
        <div class="dsc"><?php echo esc($it['description']); ?></div>
        <div class="foot">
          <span class="price"><?php echo money($it['price_cents']); ?></span>
          <form method="POST" action="index.php?action=add-to-cart" class="qty-add">
            <input type="hidden" name="item_id" value="<?php echo (int)$it['id']; ?>">
            <input type="number" name="qty" value="1" min="1" max="20">
            <button class="add-btn" type="submit">Add</button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
</div>
</body></html>
