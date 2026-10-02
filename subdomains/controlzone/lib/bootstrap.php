<?php
// PadHub - local controller store demo.
const BASE = '/subdomains/controllers';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('PADHUBSESSID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host = getenv('LAB_DB_HOST') ?: '127.0.0.1';
    $user = getenv('LAB_DB_USER') ?: 'root';
    $pass = getenv('LAB_DB_PASS') ?: '';
    $name = getenv('LAB_DB_NAME') ?: 'controllers_lab';
    $opt = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false];
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, $opt);
    } catch (PDOException $e) {
        if ((int)$e->getCode() !== 1049) throw $e;
        $root = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, $opt);
        $root->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, $opt);
        install_schema($pdo);
    }
    return $pdo;
}

function install_schema(PDO $pdo, bool $reset = false): void {
    if ($reset) foreach (['order_items', 'orders', 'cart_items', 'products', 'users'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, description TEXT, price INT NOT NULL,
        old_price INT NULL, image VARCHAR(100) NOT NULL, category VARCHAR(60) NOT NULL,
        rating DECIMAL(2,1) NOT NULL DEFAULT 4.0, reviews INT NOT NULL DEFAULT 0, stock INT NOT NULL DEFAULT 100)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL,
        UNIQUE KEY uq_user_product (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, total INT NOT NULL,
        customer_name VARCHAR(100), email VARCHAR(190), phone VARCHAR(30),
        address VARCHAR(255), city VARCHAR(100), state VARCHAR(100), postal_code VARCHAR(20), country VARCHAR(60),
        status VARCHAR(30) NOT NULL DEFAULT 'Pending Payment', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        paid_at DATETIME NULL, FOREIGN KEY (user_id) REFERENCES users(id)) AUTO_INCREMENT=1001");
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, product_name VARCHAR(190) NOT NULL,
        price INT NOT NULL, quantity INT NOT NULL, subtotal INT NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE)");

    if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() === 0) {
        $desc = [
            'Mobile Gamepads'      => 'Clip-on Bluetooth gamepad for phones. Low-latency input, 6-axis support and a long-life battery.',
            'Wireless Controllers' => 'Wireless controller with dual vibration motors, programmable buttons and fast reconnect across PC, Android and Switch.',
            'Pro Controllers'      => 'Tournament-grade controller with Hall-effect sticks, back paddles and tri-mode connectivity.',
        ];
        // name, price, old price, category, rating, reviews, body colour, accent colour
        $rows = [
            ['NiTHO V80 Bluetooth Gamepad',                  2299,  2999, 'Mobile Gamepads',      4.4, 1280, '#2b2d36', '#e5484d'],
            ['Ant Esports MG15 Super Cube Wireless Gamepad',  979,  1499, 'Wireless Controllers', 4.1,  642, '#e9ecf2', '#7a8194'],
            ['Zebronics Max Link Pro Wireless Controller',   2149,  2999, 'Wireless Controllers', 4.0,  311, '#1c2a24', '#39d98a'],
            ['Nextech Klutch One Gamepad Controller',        2162,  2999, 'Wireless Controllers', 3.9,  158, '#30343f', '#4c8dff'],
            ['NiTHO Nexus Wireless Controller',              1849,  2499, 'Wireless Controllers', 4.2,  420, '#262833', '#f5a524'],
            ['8Bitdo Ultimate 2C Wireless Controller',       2795,  3499, 'Wireless Controllers', 4.6, 2210, '#f1f2f6', '#2f3342'],
            ['SCUF Gaming Valor Pro Wireless',              18222, 26080, 'Pro Controllers',      4.5,  305, '#3a3431', '#c9a36b'],
            ['NiTHO V50 Bluetooth Gamepad',                  1599,  2099, 'Mobile Gamepads',      4.2,  903, '#22252e', '#4c8dff'],
            ['EvoFox One S Universal Wireless Controller',   1099,  1549, 'Wireless Controllers', 4.3, 1760, '#e8eaf0', '#8b92a6'],
            ['NiTHO Twinex Bluetooth Controller',             999,  1499, 'Wireless Controllers', 4.0,  517, '#25262c', '#e5484d'],
            ['Cosmic Byte Lumora Tri Mode Controller',       3499,  5999, 'Pro Controllers',      4.3,  274, '#201a33', '#b36bff'],
            ['Zebronics MAX LINK+ Wireless Controller',      2399,  3299, 'Wireless Controllers', 4.1,  189, '#1b2230', '#c6f432'],
            ['Ant Esports GP300 Pro V2',                     1399,  2999, 'Wireless Controllers', 4.2,  836, '#2a2b30', '#ff5d5d'],
            ['8BitDo Ultimate 2 Bluetooth Controller',       2500,  3499, 'Pro Controllers',      4.8,  816, '#23252b', '#2fd0ff'],
            ['Ant Esports GP320 Wireless Controller',        1598,  2499, 'Mobile Gamepads',      4.0,  298, '#f0f1f5', '#2a2d38'],
        ];
        $st = $pdo->prepare("INSERT INTO products (name,description,price,old_price,image,category,rating,reviews,stock) VALUES (?,?,?,?,?,?,?,?,100)");
        foreach ($rows as $r) $st->execute([$r[0], $desc[$r[3]], $r[1], $r[2], $r[6] . '|' . $r[7], $r[3], $r[4], $r[5]]);
    }
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
        $pdo->prepare("INSERT INTO users (name,email,password_hash) VALUES (?,?,?)")
            ->execute(['Student One', 'student@lab.local', password_hash('student123', PASSWORD_DEFAULT)]);
    }
}

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE . ($path === '' ? '' : '/' . ltrim($path, '/')); }

function inr(int $n): string {
    $neg = $n < 0;
    $s = (string)abs($n);
    if (strlen($s) > 3) {
        $s = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($s, 0, -3)) . ',' . substr($s, -3);
    }
    return ($neg ? '-' : '') . '₹' . $s;
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }

function user(): ?array {
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['uid'])) {
            $st = db()->prepare("SELECT id,name,email,created_at FROM users WHERE id=?");
            $st->execute([$_SESSION['uid']]);
            $u = $st->fetch() ?: null;
        }
    }
    return $u;
}

function require_login(): array {
    $u = user();
    if (!$u) {
        flash('error', 'Please log in to continue.');
        redirect('login');
    }
    return $u;
}

function product(int $id): ?array {
    $st = db()->prepare("SELECT * FROM products WHERE id=?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

/** Cart lines with the price always read from products.price. */
function cart_lines(int $uid): array {
    $st = db()->prepare("SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image,
                                (p.price * c.quantity) AS subtotal
                         FROM cart_items c JOIN products p ON p.id = c.product_id
                         WHERE c.user_id = ? ORDER BY c.id");
    $st->execute([$uid]);
    return $st->fetchAll();
}

function cart_count(int $uid): int {
    $st = db()->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id=?");
    $st->execute([$uid]);
    return (int)$st->fetchColumn();
}

function cart_total(array $lines): int {
    return array_reduce($lines, fn($c, $l) => $c + $l['subtotal'], 0);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

function verify_csrf(): void {
    $t = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(403); die('Invalid CSRF token.');
    }
}

// Ensure DB is initialised on every request
db();