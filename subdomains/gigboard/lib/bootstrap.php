<?php
// GigBoard — Freelancing Marketplace Training Lab
// Intentional vulnerability: plan upgrade via parameter tampering (payment_token never validated against DB).
const BASE     = '/subdomains/gigboard';
const LAB_FLAG = 'KP{plan_upgrade_parameter_tampering}';
const FREE_GIG_LIMIT = 3;

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('GIGBOARDSID');
session_start();

/* ── DATABASE ──────────────────────────────────────────── */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='gigboard_lab';
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
    if ($reset) {
        foreach(['reviews','orders','gigs','users','categories'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(80) NOT NULL, slug VARCHAR(80) NOT NULL UNIQUE, icon VARCHAR(8), sort_order INT DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('buyer','seller','both') DEFAULT 'buyer',
        plan ENUM('free','pro') DEFAULT 'free',
        bio TEXT, skills VARCHAR(255),
        avatar_color VARCHAR(12) DEFAULT '#0d9488',
        rating DECIMAL(2,1) DEFAULT 0.0, review_count INT DEFAULT 0,
        total_earnings INT DEFAULT 0,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS gigs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        seller_id INT NOT NULL, category_id INT NOT NULL,
        title VARCHAR(255) NOT NULL, description TEXT,
        price INT NOT NULL, delivery_days INT DEFAULT 3,
        rating DECIMAL(2,1) DEFAULT 4.5, review_count INT DEFAULT 0,
        orders_completed INT DEFAULT 0, featured TINYINT(1) DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(seller_id) REFERENCES users(id),
        FOREIGN KEY(category_id) REFERENCES categories(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        buyer_id INT NOT NULL, seller_id INT NOT NULL, gig_id INT NOT NULL,
        gig_title VARCHAR(255), price INT NOT NULL,
        status ENUM('pending','in_progress','delivered','completed','cancelled') DEFAULT 'pending',
        requirements TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, completed_at DATETIME NULL,
        FOREIGN KEY(buyer_id) REFERENCES users(id),
        FOREIGN KEY(seller_id) REFERENCES users(id),
        FOREIGN KEY(gig_id)    REFERENCES gigs(id)
    ) AUTO_INCREMENT=1001");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL UNIQUE, reviewer_id INT NOT NULL, gig_id INT NOT NULL,
        rating TINYINT NOT NULL, comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(order_id)    REFERENCES orders(id),
        FOREIGN KEY(reviewer_id) REFERENCES users(id),
        FOREIGN KEY(gig_id)      REFERENCES gigs(id)
    )");

    /* ── SEED CATEGORIES ── */
    if ((int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn()===0) {
        foreach([
            ['Graphic Design','design','🎨',1], ['Programming & Tech','tech','💻',2],
            ['Writing & Translation','writing','✍️',3], ['Digital Marketing','marketing','📈',4],
            ['Video & Animation','video','🎬',5], ['Music & Audio','audio','🎵',6],
            ['Business','business','💼',7],
        ] as $c) $pdo->prepare("INSERT INTO categories(name,slug,icon,sort_order) VALUES(?,?,?,?)")->execute($c);
    }

    /* ── SEED USERS ── */
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0) {
        $users=[
            ['Admin','admin@gigboard.app','admin123','both','pro','Platform admin','PHP,MySQL','#6366f1',5.0,0,0,1],
            ['Arjun Mehta','arjun@example.com','arjun123','seller','pro','Full-stack developer with 6 years exp. React, Node, PHP.','React,Node.js,PHP','#0d9488',4.9,142,0,0],
            ['Priya Singh','priya@example.com','priya123','seller','free','UI/UX Designer & Illustrator. Branding specialist.','Figma,Illustrator,Canva','#ec4899',4.8,89,0,0],
            ['Rahul Dev','rahul@example.com','rahul123','seller','free','SEO specialist and content writer. 5+ years experience.','SEO,Content,WordPress','#f59e0b',4.7,56,0,0],
            ['Sneha Kapoor','sneha@example.com','sneha123','seller','pro','Motion graphics & 2D animation. Adobe After Effects.','After Effects,Premiere,Blender','#8b5cf6',4.9,203,0,0],
            ['Demo Student','student@gigboard.lab','student123','seller','free','Learning freelancing. Want to upgrade to Pro.','PHP,Python,HTML','#0ea5e9',0.0,0,0,0],
        ];
        $st=$pdo->prepare("INSERT INTO users(name,email,password_hash,role,plan,bio,skills,avatar_color,rating,review_count,total_earnings,is_admin) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach($users as $u) {
            $st->execute([$u[0],$u[1],password_hash($u[2],PASSWORD_DEFAULT),$u[3],$u[4],$u[5],$u[6],$u[7],$u[8],$u[9],$u[10],$u[11]]);
        }
    }

    /* ── SEED GIGS ── */
    if ((int)$pdo->query("SELECT COUNT(*) FROM gigs")->fetchColumn()===0) {
        // [seller_id, cat_id, title, description, price_inr, delivery_days, rating, review_count, orders, featured]
        $gigs=[
            // Arjun Mehta (id=2) — Pro
            [2,2,'Build a Full-Stack React + Node.js Web App','Complete responsive web application with REST API, JWT auth, MySQL. Includes deployment to VPS.',14999,7,4.9,38,42,1],
            [2,2,'Fix Your PHP/JavaScript Bugs Fast','I will fix any bug in your PHP, JavaScript or React code. Fast turnaround, clean solution.',1999,1,4.8,21,28,1],
            [2,7,'Technical Consulting — 1 Hour Call','1-on-1 architecture review or code consultation. Recorded Zoom call with summary notes.',2999,1,5.0,14,14,0],
            // Priya Singh (id=3) — Free
            [3,1,'Design a Modern Logo with Brand Guidelines','3 logo concepts, unlimited revisions, full brand kit (colors, fonts, icons). Source files included.',4999,4,4.8,22,25,0],
            [3,1,'Create Social Media Post Templates','10 custom Canva/Figma templates for Instagram, LinkedIn. Editable with your brand colors.',2499,3,4.7,18,20,0],
            // Rahul Dev (id=4) — Free
            [4,4,'SEO Audit + 3-Month Strategy Report','Full technical SEO audit, competitor analysis, keyword research, actionable strategy roadmap.',7999,5,4.7,12,14,0],
            [4,3,'Write SEO-Optimised Blog Posts (1000 words)','Research-backed, plagiarism-free blog post optimised for your target keywords.',1499,2,4.6,31,35,0],
            [4,4,'Set Up Google Ads Campaign','Campaign setup, ad copy, bid strategy, conversion tracking. Includes 2-week monitoring.',5999,4,4.8,9,10,0],
            // Sneha Kapoor (id=5) — Pro
            [5,5,'Animate Your Logo — Professional Motion Graphic','Smooth logo reveal animation in MP4 and GIF format. Multiple styles: minimal, dynamic, 3D.',3499,3,4.9,47,52,1],
            [5,5,'Create a 60-Second Explainer Video','Script + voiceover + animation + music. Perfect for product launches or landing pages.',24999,10,4.9,28,31,1],
            [5,6,'Mix and Master Your Track','Professional audio mixing and mastering for any genre. Delivered in WAV and MP3.',3999,4,4.8,19,22,0],
        ];
        $si=$pdo->prepare("INSERT INTO gigs(seller_id,category_id,title,description,price,delivery_days,rating,review_count,orders_completed,featured) VALUES(?,?,?,?,?,?,?,?,?,?)");
        foreach($gigs as $g) $si->execute($g);

        // Seed some completed orders & reviews
        $orders=[
            [6,2,1,'React + Node app for e-commerce',14999,'completed','2026-09-01'],
            [6,3,4,'Logo for my startup',4999,'completed','2026-09-10'],
            [6,4,9,'Logo animation for YouTube',3499,'completed','2026-09-15'],
        ];
        $oi=$pdo->prepare("INSERT INTO orders(buyer_id,seller_id,gig_id,gig_title,price,status,completed_at) VALUES(?,?,?,?,?,?,?)");
        foreach($orders as $o) $oi->execute([$o[0],$o[1],$o[2],$o[3],$o[4],$o[5],$o[6]]);
        // Update seller earnings
        $pdo->exec("UPDATE users SET total_earnings=total_earnings+14999 WHERE id=2");
        $pdo->exec("UPDATE users SET total_earnings=total_earnings+4999  WHERE id=3");
        $pdo->exec("UPDATE users SET total_earnings=total_earnings+3499  WHERE id=5");
    }
}

/* ── HELPERS ────────────────────────────────────────────── */
function h($s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function url(string $p=''): string { return BASE.($p?'/'.ltrim($p,'/'):''); }
function inr(int $n): string {
    $s=(string)$n; if(strlen($s)>3) $s=preg_replace('/\B(?=(\d{2})+(?!\d))/',',',substr($s,0,-3).','.substr($s,-3)); return '₹'.$s;
}
function flash(string $t,string $m): void { $_SESSION['flash'][]=[$t,$m]; }
function redirect(string $p): never { header('Location:'.url($p)); exit; }

function user(): ?array {
    static $u=false; if($u!==false) return $u; $u=null;
    if(!empty($_SESSION['uid'])){
        $st=db()->prepare("SELECT id,name,email,role,plan,bio,skills,avatar_color,rating,review_count,total_earnings,is_admin FROM users WHERE id=?");
        $st->execute([$_SESSION['uid']]); $u=$st->fetch()?:null;
    }
    return $u;
}
function fresh_user(): ?array {
    if(empty($_SESSION['uid'])) return null;
    $st=db()->prepare("SELECT id,name,email,role,plan,bio,skills,avatar_color,rating,review_count,total_earnings,is_admin FROM users WHERE id=?");
    $st->execute([$_SESSION['uid']]); return $st->fetch()?:null;
}
function require_login(): array { $u=user(); if(!$u){flash('error','Please sign in.');redirect('login');} return $u; }
function stars(float $r): string { $o=''; for($i=1;$i<=5;$i++) $o.=($r>=$i?'★':($r>=$i-.5?'⯨':'☆')); return $o; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['_csrf']??'')){ http_response_code(403); die('Invalid CSRF token.'); } }
function avatar(array $u, int $size=40): string {
    $init=strtoupper(substr($u['name'],0,1)); $c=h($u['avatar_color']);
    return "<div style='width:{$size}px;height:{$size}px;border-radius:50%;background:{$c};display:inline-flex;align-items:center;justify-content:center;font-size:".round($size*.4)."px;font-weight:800;color:#fff;flex-shrink:0'>$init</div>";
}

db();
