<?php
// FlashBuy — Flash Sale Electronics Training Lab
// Intentional vulnerability: buy endpoint has no transaction/FOR UPDATE — race condition bypasses 1-per-customer limit.
const BASE     = '/subdomains/flashbuy';
const LAB_FLAG = 'KP{race_condition_purchase_limit}';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('FLASHBUYSID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='flashbuy_lab';
    $opt=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES=>false];
    try { $pdo=new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4",$user,$pass,$opt); }
    catch(PDOException $e){
        if((int)$e->getCode()!==1049) throw $e;
        $r=new PDO("mysql:host=$host;charset=utf8mb4",$user,$pass,$opt);
        $r->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
        $pdo=new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4",$user,$pass,$opt);
        install_schema($pdo);
    }
    return $pdo;
}

function install_schema(PDO $pdo, bool $reset=false): void {
    if ($reset)
        foreach(['orders','products','users'] as $t)
            $pdo->exec("DROP TABLE IF EXISTS $t");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL,
        brand VARCHAR(100), category VARCHAR(80),
        description TEXT, emoji VARCHAR(8) DEFAULT '📦',
        original_price INT NOT NULL, sale_price INT NOT NULL,
        stock INT NOT NULL DEFAULT 10, limit_per_user INT NOT NULL DEFAULT 1,
        deal_label VARCHAR(60) DEFAULT 'Flash Deal',
        rating DECIMAL(2,1) DEFAULT 4.4, review_count INT DEFAULT 0)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL,
        product_id INT NOT NULL, status ENUM('confirmed','shipped','delivered','cancelled') DEFAULT 'confirmed',
        placed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(product_id) REFERENCES products(id))");

    /* SEED PRODUCTS */
    if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn()===0) {
        // [name, brand, category, description, emoji, original_price, sale_price, stock, limit, deal_label, rating, reviews]
        $products = [
            ['Samsung Galaxy S25 Ultra','Samsung','Smartphones',
             '6.9" QHD+ Dynamic AMOLED · Snapdragon 8 Elite · 200MP camera · 5000mAh · S Pen included',
             '📱',89999,54999,5,1,'Best Phone Deal',4.7,12847],
            ['Apple iPhone 16 Pro Max','Apple','Smartphones',
             '6.9" Super Retina XDR · A18 Pro chip · 48MP triple camera system · 4422mAh · Titanium build',
             '📱',139900,89999,3,1,'Lowest Ever Price',4.8,9234],
            ['Sony PlayStation 5 Slim','Sony','Gaming',
             'PlayStation 5 Slim console with DualSense controller · Ultra HD Blu-ray · 1TB SSD · CFI-2000',
             '🎮',54990,34999,8,1,'Festival Offer',4.9,6721],
            ['Apple MacBook Air M3','Apple','Laptops',
             '13.6" Liquid Retina · Apple M3 8-core CPU · 8GB RAM · 256GB SSD · 18-hr battery · MagSafe',
             '💻',114900,79999,4,1,'Education Offer',4.8,4512],
            ['Dyson V15 Detect Absolute','Dyson','Home Appliances',
             'Laser dust detection · 60-min run time · HEPA filtration · 240AW suction · 7 attachments',
             '🌀',52900,36999,10,1,'Clearance Sale',4.6,3287],
            ['Apple Watch Series 10 GPS 46mm','Apple','Wearables',
             'Always-On Retina display · ECG · Blood oxygen · Crash detection · 36hr battery · IP6X',
             '⌚',46900,31999,6,1,'Wearable Deal',4.7,7823],
            ['Bose QuietComfort 45','Bose','Audio',
             'Industry-leading ANC · 24-hr battery · TriPort acoustic system · USB-C · Multipoint connection',
             '🎧',35000,18999,12,1,'Sound Deal',4.5,5641],
            ['Samsung 65" QLED 4K Smart TV','Samsung','Televisions',
             'Neo QLED 4K · 144Hz · Quantum HDR 32X · Dolby Atmos · 4.2.2ch 60W · Gaming Hub · 2024 model',
             '📺',189990,94999,5,1,'Biggest Discount',4.6,2918],
        ];
        $si=$pdo->prepare("INSERT INTO products(name,brand,category,description,emoji,original_price,sale_price,stock,limit_per_user,deal_label,rating,review_count) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach($products as $p) $si->execute($p);
    }

    /* SEED USERS */
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0) {
        foreach([
            ['Admin','admin@flashbuy.app','admin123',1],
            ['Flash Buyer','flash@example.com','flash123',0],
            ['Demo Student','student@flashbuy.lab','student123',0],
        ] as $u)
            $pdo->prepare("INSERT INTO users(name,email,password_hash,is_admin) VALUES(?,?,?,?)")
                ->execute([$u[0],$u[1],password_hash($u[2],PASSWORD_DEFAULT),$u[3]]);
    }
}

/* ── HELPERS ──────────────────────────────────────────────── */
function h($s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function url(string $p=''): string { return BASE.($p?'/'.ltrim($p,'/'):''); }
function inr(int $n): string {
    $s=(string)abs($n);
    if(strlen($s)>3){
        $pre=substr($s,0,-3); $suf=substr($s,-3);
        if(strlen($pre)>2) $pre=preg_replace('/\B(?=(\d{2})+(?!\d))/',',',$pre);
        $s=$pre.','.$suf;
    }
    return ($n<0?'−':'').'₹'.$s;
}
function pct(int $orig, int $sale): int { return (int)round((1-$sale/$orig)*100); }
function flash(string $t, string $m): void { $_SESSION['flash'][]=[$t,$m]; }
function redirect(string $p): never { header('Location:'.url($p)); exit; }
function user(): ?array {
    static $u=false; if($u!==false) return $u; $u=null;
    if(!empty($_SESSION['uid'])){
        $st=db()->prepare("SELECT * FROM users WHERE id=?");
        $st->execute([$_SESSION['uid']]); $u=$st->fetch()?:null;
    }
    return $u;
}
function require_login(): array { $u=user(); if(!$u){flash('error','Please sign in.');redirect('login');} return $u; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['_csrf']??'')){http_response_code(403);die('Invalid CSRF token.');} }
function stars(float $r): string { $o=''; for($i=1;$i<=5;$i++) $o.=($r>=$i?'★':($r>=$i-.5?'⯨':'☆')); return $o; }

db();
