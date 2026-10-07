<?php
// RetailZone — Fashion E-commerce Training Lab
// Intentional vulnerability: return cancellation does NOT reverse the wallet refund.
const BASE     = '/subdomains/retailzone';
const LAB_FLAG = 'KP{refund_without_return_exploit}';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('RETAILZONESID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='retailzone_lab';
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
        foreach(['wallet_txns','returns','order_items','orders','cart_items','products','categories','users'] as $t)
            $pdo->exec("DROP TABLE IF EXISTS $t");

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL,
        slug VARCHAR(80) NOT NULL UNIQUE, icon VARCHAR(8), sort_order INT DEFAULT 0)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL,
        wallet_balance INT NOT NULL DEFAULT 0,
        is_admin TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY, category_id INT NOT NULL,
        name VARCHAR(255) NOT NULL, brand VARCHAR(100), description TEXT,
        price INT NOT NULL, original_price INT NULL,
        size_options VARCHAR(255) DEFAULT 'XS,S,M,L,XL,XXL',
        color VARCHAR(60) DEFAULT 'Black', rating DECIMAL(2,1) DEFAULT 4.2,
        review_count INT DEFAULT 0, stock INT DEFAULT 100,
        FOREIGN KEY(category_id) REFERENCES categories(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1, size VARCHAR(10), color VARCHAR(30),
        UNIQUE KEY uq_up(user_id,product_id),
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(product_id) REFERENCES products(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL,
        subtotal INT NOT NULL, discount INT DEFAULT 0, shipping INT DEFAULT 0,
        wallet_used INT DEFAULT 0, total INT NOT NULL,
        address TEXT, city VARCHAR(80), state VARCHAR(80), pincode VARCHAR(10),
        payment_method VARCHAR(20) DEFAULT 'card',
        status ENUM('processing','shipped','delivered','return_initiated','return_cancelled','returned','refunded') DEFAULT 'processing',
        placed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        delivered_at DATETIME NULL, return_initiated_at DATETIME NULL,
        FOREIGN KEY(user_id) REFERENCES users(id)) AUTO_INCREMENT=1001");

    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL,
        product_id INT NOT NULL, product_name VARCHAR(255), brand VARCHAR(100),
        price INT NOT NULL, quantity INT NOT NULL, size VARCHAR(10),
        FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY(product_id) REFERENCES products(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS returns (
        id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL UNIQUE,
        user_id INT NOT NULL, reason VARCHAR(255), refund_amount INT NOT NULL,
        refund_credited TINYINT(1) DEFAULT 0,
        status ENUM('initiated','pickup_scheduled','picked_up','cancelled') DEFAULT 'initiated',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(order_id) REFERENCES orders(id),
        FOREIGN KEY(user_id)  REFERENCES users(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS wallet_txns (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, amount INT NOT NULL,
        type ENUM('credit','debit') NOT NULL, reason VARCHAR(255),
        order_id INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id))");

    /* SEED CATEGORIES */
    if ((int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn()===0) {
        foreach([['Women','women','👗',1],['Men','men','👔',2],['Kids','kids','🧒',3],
                 ['Footwear','footwear','👟',4],['Accessories','accessories','👜',5],
                 ['Beauty','beauty','💄',6]] as $c)
            $pdo->prepare("INSERT INTO categories(name,slug,icon,sort_order) VALUES(?,?,?,?)")->execute($c);
    }

    /* SEED PRODUCTS */
    if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn()===0) {
        // [cat_id, name, brand, description, price, orig_price, sizes, color, rating, reviews]
        $products=[
            // Women (1)
            [1,'Floral Wrap Midi Dress','Aurelia','Elegant floral print wrap dress. Perfect for brunches and casual outings. Flowy fabric with adjustable tie waist.',1799,2999,'XS,S,M,L,XL','Floral Print',4.5,234],
            [1,'High-Rise Skinny Jeans','AND','Premium stretch denim with high rise fit. 5-pocket style. Zip fly closure.',2499,3499,'24,26,28,30,32','Dark Blue',4.3,412],
            [1,'Embroidered Kurta Set','W','Cotton kurta with matching palazzos. Intricate embroidery on neckline. Machine washable.',1999,2799,'XS,S,M,L,XL,XXL','Beige',4.6,189],
            [1,'Ribbed Crop Sweater','H&M','Soft ribbed knit crop sweater. Relaxed fit. Great for layering.',1299,1799,'XS,S,M,L','Camel',4.2,321],
            // Men (2)
            [2,'Slim Fit Oxford Shirt','Van Heusen','Classic Oxford weave shirt. Slim fit silhouette. Button-down collar. Premium cotton.',1499,2199,'S,M,L,XL,XXL','Light Blue',4.4,567],
            [2,'Chino Trousers','Peter England','Straight fit chinos. Stretchable cotton blend. Versatile everyday wear.',1799,2499,'28,30,32,34,36','Khaki',4.3,445],
            [2,'Polo T-Shirt 3-Pack','Allen Solly','Pack of 3 polo T-shirts in different colours. Premium pique cotton. Regular fit.',2499,3499,'S,M,L,XL,XXL','Assorted',4.5,789],
            [2,'Bomber Jacket','Roadster','Street-style bomber jacket. Ribbed cuffs and hem. Zip closure with logo patch.',2999,4499,'S,M,L,XL','Olive Green',4.6,312],
            // Kids (3)
            [3,'Dino Print T-Shirt Set','H&M Kids','Set of 2 round-neck T-shirts with fun dinosaur prints. Soft 100% cotton.',799,999,'2-3Y,3-4Y,4-5Y,5-6Y,6-7Y','Dino Print',4.7,156],
            [3,'Dungaree with T-Shirt','Hopscotch','Cute dungaree set with striped T-shirt. Adjustable straps. Easy snap buttons.',1299,1799,'1-2Y,2-3Y,3-4Y,4-5Y','Blue Stripe',4.5,98],
            // Footwear (4)
            [4,'Air Cushion Running Shoes','Bata','Lightweight running shoes with air cushion sole. Breathable mesh upper. Non-slip grip.',2499,3499,'6,7,8,9,10,11','White/Blue',4.4,623],
            [4,'Block Heel Sandals','Metro','Elegant block heel sandals. Faux leather strap. Comfortable padded insole.',1699,2299,'3,4,5,6,7,8','Nude',4.3,287],
            [4,'High-Top Canvas Sneakers','Campus','Classic high-top canvas sneakers. Vulcanised rubber sole. Lace-up closure.',1299,1799,'6,7,8,9,10','Black',4.5,445],
            // Accessories (5)
            [5,'Crossbody Sling Bag','Baggit','Vegan leather crossbody bag. Multiple compartments. Adjustable strap. 7L capacity.',1499,2199,'One Size','Blush Pink',4.4,178],
            [5,'Minimalist Watch','Fastrack','Stainless steel case. Leather strap. Water resistant 50m. Japanese quartz movement.',2999,3999,'One Size','Black/Silver',4.6,534],
            [5,'Silk Scrunchie Set (5-pack)','Plum','Set of 5 silk scrunchies in pastel shades. Gentle on hair. No crease.',399,599,'One Size','Pastel Mix',4.7,892],
            // Beauty (6)
            [6,'Hydrating Face Serum 30ml','Minimalist','2% Hyaluronic Acid + PGA serum. Deep hydration. Fragrance free. For all skin types.',599,799,'One Size','—',4.6,1243],
            [6,'Matte Lipstick Quad','Lakme','Set of 4 long-lasting matte lipsticks. 12-hour wear. No feathering.',899,1199,'One Size','Nude to Red',4.4,678],
            [6,'Sunscreen SPF 50+ PA++++','Dot & Key','Lightweight waterproof sunscreen. No white cast. With niacinamide. 50ml.',699,899,'One Size','—',4.7,1567],
            [6,'Vitamin C Brightening Kit','Plum','Complete Vitamin C routine: face wash + serum + moisturiser. 30-day kit.',1499,1999,'One Size','—',4.5,421],
        ];
        $si=$pdo->prepare("INSERT INTO products(category_id,name,brand,description,price,original_price,size_options,color,rating,review_count) VALUES(?,?,?,?,?,?,?,?,?,?)");
        foreach($products as $p) $si->execute($p);
    }

    /* SEED USERS */
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0) {
        foreach([
            ['Admin','admin@retailzone.app','admin123',0,1],
            ['Priya Sharma','priya@example.com','priya123',0,0],
            ['Demo Student','student@retailzone.lab','student123',0,0],
        ] as $u)
            $pdo->prepare("INSERT INTO users(name,email,password_hash,wallet_balance,is_admin) VALUES(?,?,?,?,?)")
                ->execute([$u[0],$u[1],password_hash($u[2],PASSWORD_DEFAULT),$u[3],$u[4]]);

        // Give demo student a delivered order to return
        $student_id=(int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO orders(user_id,subtotal,discount,shipping,total,address,city,state,pincode,payment_method,status,placed_at,delivered_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$student_id,2999,0,0,2999,'42 MG Road','Bengaluru','Karnataka','560001','card','delivered','2026-09-20 10:00:00','2026-09-23 14:30:00']);
        $oid=(int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO order_items(order_id,product_id,product_name,brand,price,quantity,size) VALUES(?,?,?,?,?,?,?)")
            ->execute([$oid,8,'Bomber Jacket','Roadster',2999,1,'M']);
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
function flash(string $t, string $m): void { $_SESSION['flash'][]=[$t,$m]; }
function redirect(string $p): never { header('Location:'.url($p)); exit; }
function user(): ?array {
    static $u=false; if($u!==false) return $u; $u=null;
    if(!empty($_SESSION['uid'])){
        $st=db()->prepare("SELECT id,name,email,wallet_balance,is_admin FROM users WHERE id=?");
        $st->execute([$_SESSION['uid']]); $u=$st->fetch()?:null;
    }
    return $u;
}
function fresh_user(): ?array {
    if(empty($_SESSION['uid'])) return null;
    $st=db()->prepare("SELECT id,name,email,wallet_balance,is_admin FROM users WHERE id=?");
    $st->execute([$_SESSION['uid']]); return $st->fetch()?:null;
}
function require_login(): array { $u=user(); if(!$u){flash('error','Please sign in.');redirect('login');} return $u; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['_csrf']??'')){http_response_code(403);die('Invalid CSRF token.');} }
function stars(float $r): string { $o=''; for($i=1;$i<=5;$i++) $o.=($r>=$i?'★':($r>=$i-.5?'⯨':'☆')); return $o; }

db();
