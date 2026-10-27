<?php
// Aurora — a Shopify store
session_start();

$db = @new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'KrazePlanet');
if (!$db->connect_error) {
    $db->query("CREATE TABLE IF NOT EXISTS shopify_redirects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        old_path VARCHAR(255) NOT NULL,
        target   VARCHAR(512) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $db->query("CREATE TABLE IF NOT EXISTS shopify_customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name  VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    // A couple of ordinary store redirects so the admin list looks real.
    $cnt = $db->query("SELECT COUNT(*) c FROM shopify_redirects")->fetch_assoc()['c'] ?? 0;
    if ((int)$cnt === 0) {
        $db->query("INSERT INTO shopify_redirects (old_path, target) VALUES
            ('/shop', '/collections/all'),
            ('/summer', '/collections/summer-sale'),
            ('/14372648', 'https://evil.com')");
    }
    // A demo customer so the store has an existing account to sign in with.
    $cc = $db->query("SELECT COUNT(*) c FROM shopify_customers")->fetch_assoc()['c'] ?? 0;
    if ((int)$cc === 0) {
        $h = password_hash('password123', PASSWORD_BCRYPT);
        $st = $db->prepare("INSERT INTO shopify_customers (name,email,password_hash) VALUES ('Jordan Lee','jordan@example.com',?)");
        $st->bind_param('s', $h); $st->execute();
    }
}

$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$self     = $scheme . '://' . $host;
$hostOnly = preg_replace('/:\d+$/', '', $host);
$path     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path     = rtrim($path, '/');
if ($path === '') $path = '/';

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Work out where a login/logout redirect should actually go. Redirects are only
// allowed to stay within Shopify: the store's own host, or the shared Shopify
// checkout (checkout.shopify.com). Returns the destination URL, or null when the
// target isn't an allowed Shopify destination.
function resolve_return($url, $hostOnly, $self) {
    if ($url === '') return null;
    $h = parse_url($url, PHP_URL_HOST);
    if ($h === null) {
        return (isset($url[0]) && $url[0] === '/') ? $url : null;   // same-site relative path
    }
    $h = strtolower($h);
    if ($h === 'checkout.shopify.com') {
        // Shopify's shared checkout: route into THIS store's checkout handler,
        // which applies the store's own URL-redirect rules. Returned relative so
        // the browser keeps the current origin (and port).
        $p = parse_url($url, PHP_URL_PATH);
        if ($p === null || $p === '') $p = '/';
        return '/checkout' . $p;
    }
    if ($h === strtolower($hostOnly)) return $url;                  // store's own host
    return null;
}

function find_redirect($db, $oldPath) {
    if (!$db || $db->connect_error) return null;
    $st = $db->prepare("SELECT target FROM shopify_redirects WHERE old_path=? ORDER BY id DESC LIMIT 1");
    $st->bind_param('s', $oldPath); $st->execute();
    return $st->get_result()->fetch_assoc();
}

function current_customer($db) {
    if (empty($_SESSION['customer_id']) || !$db || $db->connect_error) return null;
    $st = $db->prepare("SELECT * FROM shopify_customers WHERE id=?");
    $st->bind_param('i', $_SESSION['customer_id']); $st->execute();
    return $st->get_result()->fetch_assoc();
}

// ── Checkout host: /checkout/<store_path> maps to the store and applies the
//    store's own URL-redirect rules (mirrors checkout.shopify.com/<store_id>). ──
if (preg_match('#^/checkout/(.+)$#', $path, $m)) {
    $storePath = '/' . $m[1];
    $rule = find_redirect($db, $storePath);
    if ($rule) { header('Location: ' . $rule['target']); exit; }
    http_response_code(404);
    render_store_404();
    exit;
}

// ── Logout: clear the session, then return the customer to ?return_url= ──
if ($path === '/account/logout') {
    unset($_SESSION['customer_id']);
    $t = resolve_return($_GET['return_url'] ?? '', $hostOnly, $self);
    if ($t !== null) { header('Location: ' . $t); exit; }
    header('Location: /'); exit;
}

// ── Login: validate credentials, then continue to ?checkout_url= ──
if ($path === '/account/login') {
    $cu = $_POST['checkout_url'] ?? ($_GET['checkout_url'] ?? '');
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $cust = null;
        if ($db && !$db->connect_error) {
            $st = $db->prepare("SELECT * FROM shopify_customers WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            $cust = $st->get_result()->fetch_assoc();
        }
        if ($cust && password_verify($pass, $cust['password_hash'])) {
            $_SESSION['customer_id'] = (int)$cust['id'];
            $t = resolve_return($cu, $hostOnly, $self);
            if ($t !== null) { header('Location: ' . $t); exit; }
            header('Location: /account'); exit;
        }
        $err = 'Incorrect email or password.';
    }
    render_login($cu, $err);
    exit;
}

// ── Create an account, then continue to ?checkout_url= ──
if ($path === '/account/register') {
    $cu = $_POST['checkout_url'] ?? ($_GET['checkout_url'] ?? '');
    $err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $err = 'Enter your name, a valid email, and a password of at least 6 characters.';
        } elseif (!$db || $db->connect_error) {
            $err = 'Service unavailable. Please try again later.';
        } else {
            $st = $db->prepare("SELECT id FROM shopify_customers WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            if ($st->get_result()->fetch_row()) {
                $err = 'An account with that email already exists.';
            } else {
                $h = password_hash($pass, PASSWORD_BCRYPT);
                $st = $db->prepare("INSERT INTO shopify_customers (name,email,password_hash) VALUES (?,?,?)");
                $st->bind_param('sss', $name, $email, $h); $st->execute();
                $_SESSION['customer_id'] = $db->insert_id;
                $t = resolve_return($cu, $hostOnly, $self);
                if ($t !== null) { header('Location: ' . $t); exit; }
                header('Location: /account'); exit;
            }
        }
    }
    render_register($cu, $err);
    exit;
}

// ── Admin: Online Store → Navigation → URL redirects (requires login) ──
if ($path === '/admin/redirects' || $path === '/admin') {
    if (!current_customer($db)) { header('Location: /account/login?checkout_url=/admin'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && !$db->connect_error) {
        $old = trim($_POST['old_path'] ?? '');
        $to  = trim($_POST['target'] ?? '');
        if ($old !== '' && $to !== '') {
            if ($old[0] !== '/') $old = '/' . $old;
            $st = $db->prepare("INSERT INTO shopify_redirects (old_path, target) VALUES (?,?)");
            $st->bind_param('ss', $old, $to); $st->execute();
        }
        header('Location: /admin/redirects'); exit;
    }
    $rows = [];
    if ($db && !$db->connect_error) {
        $res = $db->query("SELECT * FROM shopify_redirects ORDER BY id DESC");
        while ($r = $res->fetch_assoc()) $rows[] = $r;
    }
    render_admin($rows);
    exit;
}

if ($path === '/account') {
    $cust = current_customer($db);
    if (!$cust) { header('Location: /account/login'); exit; }
    render_account($cust);
    exit;
}

// ── Default: storefront home ──
render_store();

// =====================================================================
//  VIEWS
// =====================================================================
function store_head($title) { ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#1c1b1a;--muted:#6b6660;--line:#eae7e2;--bg:#faf8f5;--accent:#1c1b1a;--sage:#8a9a7b;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;color:var(--ink);background:var(--bg);line-height:1.6;}
a{text-decoration:none;color:inherit;}
.ann{background:#1c1b1a;color:#f7f3ec;text-align:center;font-size:12.5px;letter-spacing:.3px;padding:9px;}
.hd{background:#faf8f5;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:10;}
.hd-in{max-width:1120px;margin:0 auto;padding:18px 24px;display:flex;align-items:center;gap:36px;}
.logo{font-family:'Fraunces',serif;font-size:24px;font-weight:600;letter-spacing:.5px;}
.nav{display:flex;gap:26px;font-size:14px;font-weight:500;}
.nav a:hover{color:var(--muted);}
.hd-right{margin-left:auto;display:flex;gap:20px;font-size:14px;align-items:center;}
.hd-right a:hover{color:var(--muted);}
.hero{max-width:1120px;margin:0 auto;padding:60px 24px 40px;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;}
@media(max-width:800px){.hero{grid-template-columns:1fr;}.nav{display:none;}}
.hero h1{font-family:'Fraunces',serif;font-size:46px;font-weight:600;line-height:1.1;margin-bottom:16px;letter-spacing:-.5px;}
.hero p{font-size:16px;color:var(--muted);margin-bottom:26px;max-width:380px;}
.btn{display:inline-block;background:var(--ink);color:#faf8f5;padding:13px 28px;border-radius:2px;font-size:14px;font-weight:600;letter-spacing:.3px;border:none;cursor:pointer;font-family:inherit;}
.btn:hover{opacity:.88;}
.hero-img{height:360px;border-radius:4px;background:linear-gradient(135deg,#d9cfc0,#b9a98f 60%,#8a9a7b);display:flex;align-items:flex-end;padding:22px;color:#fff;font-family:'Fraunces',serif;font-size:19px;}
.sec{max-width:1120px;margin:0 auto;padding:30px 24px 70px;}
.sec h2{font-family:'Fraunces',serif;font-size:28px;font-weight:600;margin-bottom:6px;}
.sec .subtitle{color:var(--muted);font-size:14px;margin-bottom:28px;}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;}
@media(max-width:800px){.grid{grid-template-columns:repeat(2,1fr);}}
.card .ph{height:230px;border-radius:4px;margin-bottom:12px;overflow:hidden;}
.card .ph img{width:100%;height:100%;object-fit:cover;display:block;}
.card .name{font-size:14.5px;font-weight:600;}
.card .price{font-size:14px;color:var(--muted);margin-top:2px;}
.ft{background:#1c1b1a;color:#cfc8bd;margin-top:10px;}
.ft-in{max-width:1120px;margin:0 auto;padding:46px 24px;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:30px;}
@media(max-width:800px){.ft-in{grid-template-columns:1fr 1fr;}}
.ft .logo{color:#faf8f5;margin-bottom:10px;}
.ft p{font-size:13px;line-height:1.7;max-width:230px;}
.ft h4{color:#faf8f5;font-size:13px;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px;font-weight:600;}
.ft ul{list-style:none;font-size:13.5px;line-height:2;}
.ft-bot{border-top:1px solid rgba(255,255,255,.1);font-size:12px;padding:16px 24px;text-align:center;}
</style></head><body>
<div class="ann">Free shipping on orders over $75 — this week only ✦</div>
<header class="hd"><div class="hd-in">
  <a href="/" class="logo">Aurora</a>
  <nav class="nav"><a href="/">Home</a><a href="/">Shop</a><a href="/">Journal</a><a href="/">About</a></nav>
  <div class="hd-right"><a href="/">Search</a><a href="/admin">Admin</a><a href="/account/login?checkout_url=/">Log in</a><a href="/">Cart (0)</a></div>
</div></header>
<?php }

function store_foot() { ?>
<footer class="ft"><div class="ft-in">
  <div><div class="logo" style="font-family:'Fraunces',serif;font-size:22px;">Aurora</div>
    <p>Small-batch home goods and ceramics, made to be used every day.</p></div>
  <div><h4>Shop</h4><ul><li>New arrivals</li><li>Ceramics</li><li>Candles</li><li>Gift cards</li></ul></div>
  <div><h4>Company</h4><ul><li>About</li><li>Journal</li><li>Stockists</li><li>Contact</li></ul></div>
  <div><h4>Help</h4><ul><li>Shipping</li><li>Returns</li><li>FAQ</li><li>Account</li></ul></div>
</div><div class="ft-bot">© 2024 Aurora Goods. Powered by Shopify.</div></footer>
</body></html>
<?php }

function render_store() {
    $img = fn($id) => "https://images.unsplash.com/{$id}?w=560&q=70&auto=format&fit=crop";
    $products = [
        ['Terra Stoneware Mug','$28','#c9b79c',$img('photo-1514228742587-6b1558fcca3d')],
        ['Soy Candle — Cedar','$24','#b7a98f',$img('photo-1603006905003-be475563bc59')],
        ['Hand-thrown Bowl','$36','#cdbcae',$img('photo-1603199506016-b9a594b593c0')],
        ['Brass Flatware Set','$32','#c2b280',$img('photo-1607344645866-009c320b63e0')],
        ['Ceramic Vase — Dusk','$54','#9aa7a0',$img('photo-1565193566173-7a0ee3dbe261')],
        ['Everyday Dinner Plate','$22','#bcae9c',$img('photo-1544148103-0773bf10d330')],
        ['Sage Ceramic Planter','$48','#9aa78c',$img('photo-1485955900006-10f4d324d411')],
        ['Nesting Bowl Set','$42','#c9b79c',$img('photo-1610701596007-11502861dcfa')],
    ];
    store_head('Aurora — Home goods & ceramics');
    ?>
    <section class="hero">
      <div>
        <h1>Everyday things,<br>made beautifully.</h1>
        <p>Small-batch ceramics and home goods, thrown and finished by hand in our studio.</p>
        <a href="/" class="btn">Shop the collection</a>
      </div>
      <div class="hero-img">The Autumn Collection</div>
    </section>
    <section class="sec">
      <h2>Best sellers</h2>
      <div class="subtitle">Loved by our community this season.</div>
      <div class="grid">
        <?php foreach ($products as $p): ?>
        <a class="card" href="/">
          <div class="ph" style="background:<?php echo $p[2]; ?>;"><img src="<?php echo esc($p[3]); ?>" alt="" loading="lazy" onerror="this.remove()"></div>
          <div class="name"><?php echo esc($p[0]); ?></div>
          <div class="price"><?php echo esc($p[1]); ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
    store_foot();
}

function render_account($cust) {
    store_head('My account — Aurora');
    ?>
    <section class="sec" style="max-width:720px;padding-top:50px;">
      <h2>My account</h2>
      <div class="subtitle">Welcome back, <?php echo esc($cust['name']); ?> — <?php echo esc($cust['email']); ?></div>
      <div style="background:#fff;border:1px solid var(--line);border-radius:4px;padding:26px;">
        <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--line);padding-bottom:14px;margin-bottom:14px;">
          <strong>#1042</strong><span style="color:var(--sage);font-weight:600;">Fulfilled</span></div>
        <div style="color:var(--muted);font-size:14px;">Placed Oct 12, 2024 · 2 items · $70.00</div>
        <a href="/account/logout?return_url=/" class="btn" style="margin-top:22px;">Log out</a>
      </div>
    </section>
    <?php
    store_foot();
}

function render_login($checkoutUrl, $err = '') {
    store_head('Login — Aurora');
    $q = $checkoutUrl !== '' ? '?checkout_url=' . urlencode($checkoutUrl) : '';
    ?>
    <section class="sec" style="max-width:420px;padding-top:56px;padding-bottom:90px;">
      <h2 style="text-align:center;">Login</h2>
      <div class="subtitle" style="text-align:center;">Sign in to your Aurora account.</div>
      <form method="post" action="/account/login" style="background:#fff;border:1px solid var(--line);border-radius:4px;padding:28px;">
        <input type="hidden" name="checkout_url" value="<?php echo esc($checkoutUrl); ?>">
        <?php if ($err): ?><div style="background:#fff0f0;border:1px solid #f3b4b4;color:#a12020;border-radius:3px;padding:10px 12px;font-size:13px;margin-bottom:16px;"><?php echo esc($err); ?></div><?php endif; ?>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Email</label>
        <input type="email" name="email" required placeholder="you@email.com" style="width:100%;border:1px solid var(--line);border-radius:3px;padding:11px;font-size:14px;margin-bottom:16px;font-family:inherit;">
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Password</label>
        <input type="password" name="password" required placeholder="••••••••" style="width:100%;border:1px solid var(--line);border-radius:3px;padding:11px;font-size:14px;margin-bottom:20px;font-family:inherit;">
        <button class="btn" type="submit" style="width:100%;">Sign in</button>
        <div style="text-align:center;margin-top:16px;font-size:13.5px;color:var(--muted);">New customer? <a href="/account/register<?php echo $q; ?>" style="text-decoration:underline;">Create an account</a></div>
      </form>
    </section>
    <?php
    store_foot();
}

function render_register($checkoutUrl, $err = '') {
    store_head('Create account — Aurora');
    $q = $checkoutUrl !== '' ? '?checkout_url=' . urlencode($checkoutUrl) : '';
    ?>
    <section class="sec" style="max-width:420px;padding-top:56px;padding-bottom:90px;">
      <h2 style="text-align:center;">Create account</h2>
      <div class="subtitle" style="text-align:center;">Join Aurora to track orders and save your details.</div>
      <form method="post" action="/account/register" style="background:#fff;border:1px solid var(--line);border-radius:4px;padding:28px;">
        <input type="hidden" name="checkout_url" value="<?php echo esc($checkoutUrl); ?>">
        <?php if ($err): ?><div style="background:#fff0f0;border:1px solid #f3b4b4;color:#a12020;border-radius:3px;padding:10px 12px;font-size:13px;margin-bottom:16px;"><?php echo esc($err); ?></div><?php endif; ?>
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Name</label>
        <input type="text" name="name" required placeholder="First and last name" style="width:100%;border:1px solid var(--line);border-radius:3px;padding:11px;font-size:14px;margin-bottom:16px;font-family:inherit;">
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Email</label>
        <input type="email" name="email" required placeholder="you@email.com" style="width:100%;border:1px solid var(--line);border-radius:3px;padding:11px;font-size:14px;margin-bottom:16px;font-family:inherit;">
        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Password</label>
        <input type="password" name="password" required placeholder="At least 6 characters" style="width:100%;border:1px solid var(--line);border-radius:3px;padding:11px;font-size:14px;margin-bottom:20px;font-family:inherit;">
        <button class="btn" type="submit" style="width:100%;">Create account</button>
        <div style="text-align:center;margin-top:16px;font-size:13.5px;color:var(--muted);">Already have an account? <a href="/account/login<?php echo $q; ?>" style="text-decoration:underline;">Sign in</a></div>
      </form>
    </section>
    <?php
    store_foot();
}

function render_store_404() {
    store_head('Page not found — Aurora');
    ?>
    <section class="sec" style="text-align:center;padding:90px 24px;">
      <h2 style="font-size:40px;">404</h2>
      <div class="subtitle">The page you were looking for doesn't exist.</div>
      <a href="/" class="btn">Back to store</a>
    </section>
    <?php
    store_foot();
}

// ── Shopify admin: URL redirects ──
function render_admin($rows) {
    ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>URL redirects · Navigation · Aurora</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--ink:#212b36;--muted:#637381;--line:#dfe3e8;--bg:#f4f6f8;--green:#008060;--blue:#2c6ecb;--side:#1a1c1d;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',-apple-system,sans-serif;background:var(--bg);color:var(--ink);font-size:14px;}
a{text-decoration:none;color:var(--blue);}
.shell{display:grid;grid-template-columns:232px 1fr;min-height:100vh;}
.side{background:var(--side);color:#c9cccf;padding:12px 0;}
.side-brand{display:flex;align-items:center;gap:9px;padding:6px 18px 16px;color:#fff;font-weight:700;font-size:15px;}
.side-brand .sq{width:26px;height:26px;border-radius:7px;background:#95bf47;display:flex;align-items:center;justify-content:center;color:#1a1c1d;font-weight:800;}
.side-sec{font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#8c9196;padding:14px 18px 6px;}
.side a{display:block;color:#c9cccf;padding:7px 18px;font-size:13.5px;}
.side a:hover{background:rgba(255,255,255,.06);color:#fff;}
.side a.active{background:rgba(255,255,255,.1);color:#fff;font-weight:600;border-left:3px solid #95bf47;padding-left:15px;}
.top{background:#fff;border-bottom:1px solid var(--line);height:56px;display:flex;align-items:center;padding:0 22px;gap:10px;font-size:13px;color:var(--muted);}
.top .crumb{color:var(--ink);font-weight:600;}
.main{padding:24px;max-width:1000px;}
.page-title{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.page-title h1{font-size:20px;font-weight:700;}
.btn{background:var(--green);color:#fff;border:none;border-radius:8px;padding:9px 16px;font-size:13.5px;font-weight:600;cursor:pointer;font-family:inherit;}
.btn:hover{background:#006e52;}
.card{background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 1px 0 rgba(22,29,37,.05);overflow:hidden;margin-bottom:20px;}
.card-head{padding:13px 18px;border-bottom:1px solid var(--line);font-weight:600;font-size:14px;}
.search{padding:12px 18px;border-bottom:1px solid var(--line);}
.search input{width:100%;border:1px solid var(--line);border-radius:8px;padding:8px 12px;font-size:13.5px;font-family:inherit;}
table{width:100%;border-collapse:collapse;}
th,td{text-align:left;padding:12px 18px;font-size:13.5px;}
th{font-size:12px;color:var(--muted);font-weight:600;background:#fafbfc;border-bottom:1px solid var(--line);}
tbody tr{border-bottom:1px solid var(--line);}
tbody tr:last-child{border-bottom:none;}
td code{font-family:ui-monospace,Menlo,monospace;font-size:13px;}
.note{color:var(--muted);font-size:13px;padding:14px 18px;}
.form-row{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;padding:18px;align-items:end;}
@media(max-width:700px){.form-row{grid-template-columns:1fr;}.shell{grid-template-columns:1fr;}.side{display:none;}}
.form-row label{display:block;font-size:12.5px;font-weight:600;margin-bottom:5px;}
.form-row input{width:100%;border:1px solid var(--line);border-radius:8px;padding:9px 11px;font-size:13.5px;font-family:inherit;}
.hint{font-size:12px;color:var(--muted);margin-top:4px;}
</style></head><body>
<div class="shell">
  <aside class="side">
    <div class="side-brand"><span class="sq">A</span> Aurora</div>
    <div class="side-sec">Online Store</div>
    <a href="/admin/redirects">Themes</a>
    <a href="/admin/redirects">Blog posts</a>
    <a href="/admin/redirects">Pages</a>
    <a href="/admin/redirects" class="active">Navigation</a>
    <a href="/admin/redirects">Domains</a>
    <a href="/admin/redirects">Preferences</a>
  </aside>
  <div>
    <div class="top">Online Store <span>/</span> Navigation <span>/</span> <span class="crumb">URL redirects</span></div>
    <div class="main">
      <div class="page-title"><h1>URL redirects</h1></div>

      <div class="card">
        <div class="card-head">All redirects</div>
        <div class="search"><input placeholder="Start typing to search for redirects…"></div>
        <table>
          <thead><tr><th>Old path</th><th>Redirect to</th></tr></thead>
          <tbody>
            <?php if (empty($rows)): ?>
            <tr><td colspan="2" class="note">No redirects yet.</td></tr>
            <?php else: foreach ($rows as $r): ?>
            <tr><td><code><?php echo esc($r['old_path']); ?></code></td><td><a href="<?php echo esc($r['target']); ?>"><?php echo esc($r['target']); ?></a></td></tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
        <div class="note">URL redirects prevent bookmarks and links to your previous site from breaking.</div>
      </div>

      <div class="card">
        <div class="card-head">Add URL redirect</div>
        <form method="post" action="/admin/redirects" class="form-row">
          <div><label>Old path</label><input name="old_path" placeholder="/old-product" required>
            <div class="hint">The path on your store to redirect from.</div></div>
          <div><label>Redirect to</label><input name="target" placeholder="/collections/all" required>
            <div class="hint">A path on your store, or a full URL.</div></div>
          <div><button class="btn" type="submit">Save redirect</button></div>
        </form>
      </div>
    </div>
  </div>
</div>
</body></html>
<?php }
