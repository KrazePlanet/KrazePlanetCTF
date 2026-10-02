<?php
// PCHub - PC Hardware Training Lab
// Intentionally vulnerable coupon system for security education.
const BASE     = '/subdomains/controllers';
const LAB_FLAG = 'KP{coupon_business_logic_bypass}';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('PCHUBBID');
session_start();

/* ── DATABASE ────────────────────────────────────────────── */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host = getenv('LAB_DB_HOST') ?: '127.0.0.1';
    $user = getenv('LAB_DB_USER') ?: 'root';
    $pass = getenv('LAB_DB_PASS') ?: '';
    $name = getenv('LAB_DB_NAME') ?: 'pchub_lab';
    $opt  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
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
    if ($reset) {
        foreach (['order_items','orders','cart_items','coupons','products','categories','users'] as $t)
            $pdo->exec("DROP TABLE IF EXISTS $t");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) NOT NULL UNIQUE, icon VARCHAR(8) DEFAULT '💻', sort_order INT DEFAULT 0)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY, category_id INT NOT NULL,
        name VARCHAR(255) NOT NULL, brand VARCHAR(100) NOT NULL,
        description TEXT, specs TEXT, price INT NOT NULL, original_price INT NULL,
        stock INT NOT NULL DEFAULT 50, rating DECIMAL(2,1) DEFAULT 4.0,
        review_count INT DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        UNIQUE KEY uq_up (user_id, product_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) NOT NULL UNIQUE,
        discount_pct INT NOT NULL, description VARCHAR(255),
        intended_category_id INT NULL, active TINYINT(1) DEFAULT 1,
        FOREIGN KEY (intended_category_id) REFERENCES categories(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL,
        subtotal INT NOT NULL, discount INT NOT NULL DEFAULT 0,
        coupon_code VARCHAR(50) NULL, coupon_applications INT DEFAULT 0,
        shipping INT NOT NULL DEFAULT 0, total INT NOT NULL,
        customer_name VARCHAR(100), email VARCHAR(190), phone VARCHAR(30),
        address VARCHAR(255), city VARCHAR(100), state VARCHAR(100),
        postal_code VARCHAR(20), country VARCHAR(60),
        payment_method VARCHAR(30) DEFAULT 'card',
        status VARCHAR(30) NOT NULL DEFAULT 'Pending Payment',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, paid_at DATETIME NULL,
        FOREIGN KEY (user_id) REFERENCES users(id)) AUTO_INCREMENT=1001");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL,
        product_id INT NOT NULL, product_name VARCHAR(255) NOT NULL,
        brand VARCHAR(100), price INT NOT NULL, quantity INT NOT NULL, subtotal INT NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE)");

    /* ── SEED CATEGORIES ── */
    if ((int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn() === 0) {
        $cats = [
            ['Graphics Cards', 'gpu',         '🎮', 1],
            ['Processors',     'cpu',         '⚡', 2],
            ['RAM',            'ram',         '💾', 3],
            ['SSD',            'ssd',         '⚡', 4],
            ['Hard Drives',    'hdd',         '💿', 5],
            ['Motherboards',   'mb',          '🔌', 6],
            ['Power Supplies', 'psu',         '🔋', 7],
            ['PC Cases',       'case',        '🖥️', 8],
            ['CPU Coolers',    'cooler',      '❄️', 9],
            ['Accessories',    'accessories', '🎧',10],
        ];
        $sc = $pdo->prepare("INSERT INTO categories (name,slug,icon,sort_order) VALUES (?,?,?,?)");
        foreach ($cats as $c) $sc->execute($c);
    }

    /* ── SEED PRODUCTS (36) ── */
    if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() === 0) {
        // [name, brand, cat_id, price, orig_price, rating, reviews, description, specs]
        $p = [
            /* ── GPUs (cat 1) ── */
            ['NVIDIA GeForce RTX 5090',      'NVIDIA',  1, 199999, 219999, 4.9,  412,
             'The flagship Ada Next-gen GPU with 32GB GDDR7. Dominates 4K gaming and AI workloads.',
             'Memory: 32GB GDDR7 | Bus: 512-bit | Boost: 2.9GHz | TDP: 450W | Ports: 3×DP 2.1 + HDMI 2.1'],
            ['NVIDIA GeForce RTX 5080',      'NVIDIA',  1, 129999, 139999, 4.8,  621,
             'Flagship-class performance for 4K gaming. 24GB GDDR7 with DLSS 4 support.',
             'Memory: 24GB GDDR7 | Bus: 384-bit | Boost: 2.85GHz | TDP: 320W | Ports: 3×DP 2.1 + HDMI 2.1'],
            ['NVIDIA GeForce RTX 5070 Ti',   'NVIDIA',  1,  79999,  84999, 4.7,  834,
             'Perfect 1440p and 4K card with 16GB GDDR7 and Ada architecture efficiency.',
             'Memory: 16GB GDDR7 | Bus: 256-bit | Boost: 2.80GHz | TDP: 285W | Ports: 3×DP 2.1 + HDMI 2.1'],
            ['NVIDIA GeForce RTX 5070',      'NVIDIA',  1,  59999,  64999, 4.6, 1204,
             'The sweet-spot 1440p GPU with 12GB GDDR7. Excellent for high-refresh gaming.',
             'Memory: 12GB GDDR7 | Bus: 192-bit | Boost: 2.75GHz | TDP: 250W | Ports: 3×DP 2.1 + HDMI 2.1'],
            ['AMD Radeon RX 9070 XT',        'AMD',     1,  54999,  59999, 4.6,  742,
             'RDNA 4 flagship delivers RTX 5070 Ti competition at an aggressive price point.',
             'Memory: 16GB GDDR6 | Bus: 256-bit | Boost: 3.0GHz | TDP: 280W | Ports: 2×DP 2.1 + 2×HDMI 2.1'],
            ['AMD Radeon RX 9070',           'AMD',     1,  44999,  49999, 4.5,  519,
             'Excellent 1440p card with 16GB GDDR6. Great price-performance ratio.',
             'Memory: 16GB GDDR6 | Bus: 256-bit | Boost: 2.87GHz | TDP: 220W | Ports: 2×DP 2.1 + 2×HDMI 2.1'],

            /* ── CPUs (cat 2) ── */
            ['AMD Ryzen 9 9950X',            'AMD',     2,  59999,  69999, 4.9,  328,
             '16-core 32-thread workstation monster on Zen 5. Best AMD desktop CPU 2025.',
             'Cores: 16C/32T | Base: 4.3GHz | Boost: 5.7GHz | L3 Cache: 64MB | TDP: 170W | Socket: AM5'],
            ['AMD Ryzen 7 9800X3D',          'AMD',     2,  44999,  47999, 4.9,  562,
             'World\'s fastest gaming CPU with 3D V-Cache. Unbeatable in gaming workloads.',
             'Cores: 8C/16T | Base: 4.7GHz | Boost: 5.2GHz | L3 Cache: 96MB 3D | TDP: 120W | Socket: AM5'],
            ['AMD Ryzen 7 9700X',            'AMD',     2,  34999,  39999, 4.7,  481,
             '8-core Zen 5 processor with great all-round performance and efficiency.',
             'Cores: 8C/16T | Base: 3.8GHz | Boost: 5.5GHz | L3 Cache: 32MB | TDP: 65W | Socket: AM5'],
            ['AMD Ryzen 5 9600X',            'AMD',     2,  22999,  27999, 4.6,  734,
             'The best budget-to-mid gaming CPU. 6 Zen 5 cores with excellent single-thread.',
             'Cores: 6C/12T | Base: 3.9GHz | Boost: 5.4GHz | L3 Cache: 32MB | TDP: 65W | Socket: AM5'],
            ['Intel Core Ultra 9 285K',      'Intel',   2,  52999,  57999, 4.7,  289,
             'Intel\'s Arrow Lake flagship with 24-core hybrid architecture. Top productivity.',
             'Cores: 8P+16E | Base: 3.7GHz | Boost: 5.7GHz | L2+L3: 45MB | TDP: 125W | Socket: LGA1851'],
            ['Intel Core Ultra 7 265K',      'Intel',   2,  37999,  42999, 4.6,  413,
             'Mid-range Arrow Lake powerhouse. Solid gaming and productivity CPU.',
             'Cores: 8P+12E | Base: 3.9GHz | Boost: 5.5GHz | L2+L3: 36MB | TDP: 125W | Socket: LGA1851'],

            /* ── RAM (cat 3) ── */
            ['Corsair Vengeance 32GB DDR5-6000',   'Corsair',  3,  12999,  14999, 4.7,  892,
             '2×16GB DDR5-6000 kit with Intel XMP 3.0 and AMD EXPO. Low-profile white/black.',
             '2×16GB | DDR5-6000 | CL30 | 1.35V | XMP 3.0 + EXPO | Lifetime Warranty'],
            ['Corsair Vengeance 16GB DDR5-6000',   'Corsair',  3,   6999,   8499, 4.6, 1247,
             '2×8GB DDR5-6000. Ideal for budget AM5/LGA1851 builds with solid OC headroom.',
             '2×8GB | DDR5-6000 | CL30 | 1.35V | XMP 3.0 + EXPO | Lifetime Warranty'],
            ['G.Skill Trident Z5 32GB DDR5-7200',  'G.Skill',  3,  15999,  18499, 4.8,  407,
             'Extreme OC DDR5 kit in the iconic Trident Z5 design. Top of the overclocking charts.',
             '2×16GB | DDR5-7200 | CL34 | 1.45V | XMP 3.0 | JEDEC Certified | Lifetime Warranty'],
            ['Kingston Fury Beast 32GB DDR5-5200', 'Kingston', 3,  11999,  13999, 4.5,  631,
             'Reliable workhorse DDR5 kit. Plug-and-play with AMD EXPO and Intel XMP 3.0.',
             '2×16GB | DDR5-5200 | CL40 | 1.25V | XMP 3.0 + EXPO | Lifetime Warranty'],

            /* ── SSD (cat 4) ── */
            ['Samsung 990 Pro 2TB NVMe',     'Samsung', 4,  14999,  17999, 4.9, 1521,
             'PCIe 4.0 M.2 NVMe SSD. 7450/6900 MB/s reads/writes. King of consumer SSDs.',
             'Interface: PCIe 4.0 x4 | Form: M.2 2280 | Read: 7450MB/s | Write: 6900MB/s | TBW: 1200'],
            ['Samsung 990 Pro 1TB NVMe',     'Samsung', 4,   8999,  10999, 4.8, 2143,
             'Best-in-class 1TB NVMe SSD with thermal throttle protection and great endurance.',
             'Interface: PCIe 4.0 x4 | Form: M.2 2280 | Read: 7450MB/s | Write: 6900MB/s | TBW: 600'],
            ['WD Black SN850X 2TB',          'WD',      4,  13499,  15999, 4.7,  891,
             'PlayStation-certified PCIe 4.0 NVMe SSD with optional heatsink. Gaming-optimized.',
             'Interface: PCIe 4.0 x4 | Form: M.2 2280 | Read: 7300MB/s | Write: 6600MB/s | TBW: 1200'],
            ['Crucial T500 1TB NVMe',        'Crucial', 4,   7499,   8999, 4.6,  742,
             'Value-king PCIe 4.0 NVMe at the best price-per-GB. Excellent everyday performer.',
             'Interface: PCIe 4.0 x4 | Form: M.2 2280 | Read: 7300MB/s | Write: 6800MB/s | TBW: 600'],

            /* ── HDD (cat 5) ── */
            ['WD Blue 2TB Desktop HDD',      'WD',      5,   4299,   4999, 4.4, 2847,
             'Reliable 7200RPM SATA hard drive. Perfect secondary storage for games and files.',
             'Capacity: 2TB | RPM: 7200 | Cache: 256MB | Interface: SATA 6Gb/s | Form: 3.5"'],
            ['Seagate Barracuda 2TB',        'Seagate', 5,   3999,   4799, 4.3, 3421,
             'The world\'s most popular desktop HDD. Fast, reliable, affordable storage.',
             'Capacity: 2TB | RPM: 7200 | Cache: 256MB | Interface: SATA 6Gb/s | Form: 3.5"'],
            ['Seagate IronWolf 4TB NAS',     'Seagate', 5,   8499,   9999, 4.6,  892,
             'NAS-grade CMR HDD built for 24/7 operation. 3-year warranty with IronWolf Health.',
             'Capacity: 4TB | RPM: 5400 | Cache: 256MB | Interface: SATA 6Gb/s | Form: 3.5" | NAS-rated'],

            /* ── Motherboards (cat 6) ── */
            ['ASUS ROG Strix X870E-E Gaming WiFi', 'ASUS',     6,  42999,  49999, 4.8,  234,
             'Flagship AM5 board with WiFi 7, 5GbE LAN, 20+2 VRM phases. Premium ROG aesthetics.',
             'Socket: AM5 | Chipset: X870E | DDR5: 4×192GB max | PCIe 5.0 x16 | WiFi 7 | USB4 | ATX'],
            ['MSI MAG B650 Tomahawk WiFi',        'MSI',      6,  18999,  21999, 4.6,  672,
             'Best mid-range AM5 board. Robust VRM, WiFi 6E, 2.5GbE. Solid for Ryzen 7/9.',
             'Socket: AM5 | Chipset: B650 | DDR5: 4×192GB max | PCIe 5.0 x16 | WiFi 6E | 2.5GbE | ATX'],
            ['Gigabyte AORUS Elite AX B650',      'Gigabyte', 6,  22999,  26999, 4.7,  428,
             'Feature-packed B650 board with PCIe 5.0 M.2, WiFi 6E, and premium AORUS design.',
             'Socket: AM5 | Chipset: B650 | DDR5: 4×128GB max | PCIe 5.0 M.2 | WiFi 6E | 2.5GbE | ATX'],

            /* ── PSU (cat 7) ── */
            ['Corsair RM1000x 1000W 80+ Gold', 'Corsair', 7,  14999,  17999, 4.8,  567,
             'Fully modular 1000W 80+ Gold PSU. Zero-RPM fan mode, 10-year warranty.',
             '1000W | 80+ Gold | Fully Modular | Zero RPM Mode | ATX 3.0 | PCIe 5.0 | 10-year warranty'],
            ['EVGA SuperNOVA 750W 80+ Gold',  'EVGA',    7,   9999,  11999, 4.7,  834,
             'Trusted 750W PSU for mid-high builds. Fully modular with excellent efficiency.',
             '750W | 80+ Gold | Fully Modular | ECO Mode | ATX 3.0 | 10-year warranty'],

            /* ── Cases (cat 8) ── */
            ['Lian Li PC-O11D EVO RGB',       'Lian Li', 8,  12999,  14999, 4.8,  423,
             'Iconic O11 design with triple-radiator support. Dual-chamber for clean cable management.',
             'Form: Mid-Tower | MB: E-ATX/ATX/mATX | Radiator: 360mm top/side/bottom | Tempered glass'],
            ['NZXT H7 Flow RGB',              'NZXT',    8,   9499,  10999, 4.7,  312,
             'Airflow-optimized mid-tower with RGB lighting and refined minimalist design.',
             'Form: Mid-Tower | MB: E-ATX/ATX/mATX | Radiator: 360mm front/top | Tempered glass | USB-C'],

            /* ── Coolers (cat 9) ── */
            ['Noctua NH-D15 G2',              'Noctua',  9,   9999,  10999, 4.9,  789,
             'The legendary dual-tower CPU cooler reborn. Cools 280W TDP CPUs without liquid.',
             'Type: Air Cooler | Fans: 2×140mm NF-A15 | TDP: 280W+ | Height: 168mm | AM5/LGA1851'],
            ['Arctic Liquid Freezer III 360', 'Arctic',  9,   8499,   9499, 4.7,  521,
             '360mm AIO with optimized cold plate, VRM fan, and whisper-quiet operation.',
             'Type: 360mm AIO | Fan: 3×120mm | TDP: 400W+ | Pump: VRM Cooling | AM5/LGA1851'],

            /* ── Accessories (cat 10) ── */
            ['Logitech G Pro X Superlight 2', 'Logitech', 10,  9999,  12999, 4.8, 1432,
             'Esports-grade wireless gaming mouse. 60g ultra-lightweight with HERO 2 25K sensor.',
             'Sensor: HERO 2 25K | DPI: 100-25600 | Buttons: 5 | Weight: 60g | Battery: 95hr | USB-C'],
            ['Corsair K70 RGB Pro Mechanical','Corsair',  10,  7999,   9999, 4.6,  876,
             'Full-size mechanical gaming keyboard with per-key RGB and Cherry MX switches.',
             'Switch: Cherry MX Red | Layout: Full 104-key | Backlight: RGB per-key | USB passthrough'],
            ['Samsung 32" 4K UHD Monitor M7', 'Samsung', 10, 29999,  34999, 4.7,  634,
             'Smart 32" 4K monitor with built-in streaming apps, USB-C 65W delivery, and HDR400.',
             'Panel: 32" IPS | Resolution: 3840×2160 | Refresh: 60Hz | HDR400 | USB-C 65W | Smart TV OS'],
            ['Logitech G935 Wireless Headset', 'Logitech',10,  8499,  10999, 4.5,  921,
             '7.1 surround-sound wireless gaming headset with 50mm Pro-G drivers.',
             'Driver: 50mm Pro-G | Wireless: LIGHTSPEED 2.4GHz + BT | Battery: 51hr | Mic: Flip-to-mute'],
        ];

        $si = $pdo->prepare("INSERT INTO products (name,brand,category_id,price,original_price,rating,review_count,description,specs,stock) VALUES (?,?,?,?,?,?,?,?,?,50)");
        foreach ($p as $r) $si->execute([$r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$r[6],$r[7],$r[8]]);
    }

    /* ── SEED COUPON ── */
    if ((int)$pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn() === 0) {
        // intended_category_id = 10 (Accessories) but NOT enforced server-side (intentional bug)
        $pdo->prepare("INSERT INTO coupons (code,discount_pct,description,intended_category_id,active) VALUES (?,?,?,?,1)")
            ->execute(['WELCOME20', 20, 'Welcome coupon — 20% off accessories', 10]);
        $pdo->prepare("INSERT INTO coupons (code,discount_pct,description,intended_category_id,active) VALUES (?,?,?,?,1)")
            ->execute(['SAVE10', 10, '10% off sitewide', null]);
    }

    /* ── SEED USERS ── */
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
        $pdo->prepare("INSERT INTO users (name,email,password_hash,is_admin) VALUES (?,?,?,1)")
            ->execute(['Admin', 'admin@pchub.lab', password_hash('admin123', PASSWORD_DEFAULT)]);
        $pdo->prepare("INSERT INTO users (name,email,password_hash,is_admin) VALUES (?,?,?,0)")
            ->execute(['Student Demo', 'student@pchub.lab', password_hash('student123', PASSWORD_DEFAULT)]);
    }
}

/* ── HELPERS ─────────────────────────────────────────────── */
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE . ($p === '' ? '' : '/' . ltrim($p, '/')); }

function inr(int $n): string {
    $neg = $n < 0; $s = (string)abs($n);
    if (strlen($s) > 3) $s = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($s, 0, -3)) . ',' . substr($s, -3);
    return ($neg ? '-' : '') . '₹' . $s;
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }

function user(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare("SELECT id,name,email,is_admin,created_at FROM users WHERE id=?");
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}

function require_login(): array {
    $u = user();
    if (!$u) { flash('error', 'Please log in to continue.'); redirect('login'); }
    return $u;
}

function is_admin(): bool { $u = user(); return $u && (bool)$u['is_admin']; }

function product(int $id): ?array {
    $st = db()->prepare("SELECT p.*, c.name AS cat_name, c.slug AS cat_slug FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=?");
    $st->execute([$id]); return $st->fetch() ?: null;
}

function cart_lines(int $uid): array {
    $st = db()->prepare(
        "SELECT c.id, c.product_id, c.quantity, p.name, p.brand, p.price,
                cat.slug AS cat_slug, (p.price * c.quantity) AS subtotal
         FROM cart_items c
         JOIN products p ON p.id = c.product_id
         JOIN categories cat ON cat.id = p.category_id
         WHERE c.user_id = ? ORDER BY c.id");
    $st->execute([$uid]); return $st->fetchAll();
}

function cart_count(int $uid): int {
    $st = db()->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id=?");
    $st->execute([$uid]); return (int)$st->fetchColumn();
}

function cart_subtotal(array $lines): int {
    return array_reduce($lines, fn($c, $l) => $c + $l['subtotal'], 0);
}

/* ── COUPON HELPERS ── */
function get_coupon(): array  { return $_SESSION['coupon'] ?? []; }
function coupon_factor(): float { $c = get_coupon(); return (float)($c['factor'] ?? 1.0); }

function coupon_discount(int $subtotal): int {
    $c = get_coupon(); if (!$c) return 0;
    return $subtotal - (int)round($subtotal * $c['factor']);
}

function stars(float $r): string {
    $out = ''; for ($i = 1; $i <= 5; $i++) $out .= ($r >= $i ? '★' : ($r >= $i - 0.5 ? '⯨' : '☆'));
    return $out;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? ''))
        { http_response_code(403); die('Invalid CSRF token.'); }
}

// Auto-init DB on every request
db();
