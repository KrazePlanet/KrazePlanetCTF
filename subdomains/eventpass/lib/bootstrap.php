<?php
// EventPass — Event Ticketing Platform Training Lab
// Intentional vulnerability: POST /bookings/cancel accepts ticket_id from POST body
// with no ownership check — any user can cancel any other user's ticket (IDOR).
const BASE     = '/subdomains/eventpass';
const LAB_FLAG = 'KP{idor_ticket_cancellation_hijack}';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('EVENTPASSSID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='eventpass_lab';
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
    if($reset)
        foreach(['tickets','events','users'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        wallet_paise INT DEFAULT 0,
        is_organizer TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        category ENUM('concert','sports','comedy','conference','festival') NOT NULL,
        venue VARCHAR(200) NOT NULL,
        city VARCHAR(80) NOT NULL,
        event_date DATE NOT NULL,
        event_time VARCHAR(20) NOT NULL,
        price_paise INT NOT NULL,
        total_seats INT NOT NULL,
        available_seats INT NOT NULL,
        emoji VARCHAR(8) DEFAULT '🎤',
        description TEXT,
        is_sold_out TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        event_id INT NOT NULL,
        seat_no VARCHAR(20) NOT NULL,
        status ENUM('confirmed','cancelled') DEFAULT 'confirmed',
        cancelled_by INT NULL,
        booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(event_id) REFERENCES events(id))");

    if((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0){
        $seed_users=[
            ['EventPass Admin','organizer@eventpass.io','org123',0,1],
            ['Alice Fan','alice@example.com','alice123',50000,0],
            ['Bob Sports','bob@example.com','bob123',75000,0],
            ['Student','student@eventpass.lab','student123',20000,0],
        ];
        $ui=$pdo->prepare("INSERT INTO users(name,email,password_hash,wallet_paise,is_organizer) VALUES(?,?,?,?,?)");
        foreach($seed_users as $u)
            $ui->execute([$u[0],$u[1],password_hash($u[2],PASSWORD_DEFAULT),$u[3],$u[4]]);

        $ids=[];
        foreach(['organizer@eventpass.io','alice@example.com','bob@example.com','student@eventpass.lab'] as $e)
            $ids[]=(int)$pdo->query("SELECT id FROM users WHERE email='$e'")->fetchColumn();
        [$org,$alice,$bob,$student]=$ids;

        $seed_events=[
            ['Arijit Singh Live — The Emotions Tour','concert','DY Patil Stadium','Mumbai','2024-12-15','07:00 PM',150000,40000,1,'🎤','The most awaited concert of the year! Arijit Singh performs his greatest hits live.',1],
            ['IPL Finals 2025 — MI vs CSK','sports','Wankhede Stadium','Mumbai','2025-03-28','03:30 PM',200000,33000,200,'🏏','Witness the clash of titans! Book your seats for the most thrilling T20 final.',0],
            ['Zakir Khan — "Suniye Toh Bhaiyya" Comedy Night','comedy','NESCO Grounds','Mumbai','2024-11-30','08:00 PM',75000,5000,150,'😂','Zakir Khan returns with his signature storytelling and laugh-out-loud humour.',0],
            ['TechConf 2025 — Future of AI','conference','Bombay Exhibition Centre','Mumbai','2025-01-18','09:00 AM',50000,2000,300,'💻','Keynotes, workshops, and networking with India\'s top tech leaders.',0],
            ['Sunburn Goa 2025','festival','Vagator Beach','Goa','2025-12-27','04:00 PM',250000,5000,1200,'🎉','Asia\'s biggest EDM festival returns to the beaches of Goa!',0],
        ];
        $ei=$pdo->prepare("INSERT INTO events(title,category,venue,city,event_date,event_time,price_paise,total_seats,available_seats,emoji,description,is_sold_out) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach($seed_events as $e) $ei->execute($e);

        $ids2=[];
        foreach(range(1,5) as $i)
            $ids2[]=(int)$pdo->query("SELECT id FROM events WHERE id=$i")->fetchColumn();
        [$ev1,$ev2,$ev3,$ev4,$ev5]=$ids2;

        // Alice has ticket #3 for the sold-out Arijit Singh concert (event 1)
        // Bob has ticket for IPL
        // Student has ticket for comedy night
        $ti=$pdo->prepare("INSERT INTO tickets(user_id,event_id,seat_no,status) VALUES(?,?,?,?)");
        $ti->execute([$alice,$ev1,'P-14','confirmed']); // ticket ID 1 — alice, sold-out concert
        $ti->execute([$alice,$ev1,'P-15','confirmed']); // ticket ID 2 — alice 2nd seat
        $ti->execute([$alice,$ev1,'P-16','confirmed']); // ticket ID 3 — alice 3rd seat (main target)
        $ti->execute([$bob,$ev2,'E-42','confirmed']);   // ticket ID 4 — bob, IPL
        $ti->execute([$student,$ev3,'D-07','confirmed']); // ticket ID 5 — student, comedy
    }
}

/* ── HELPERS ──────────────────────────────────────────────── */
function h($s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function url(string $p=''): string { return BASE.($p?'/'.ltrim($p,'/'):''); }
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
function fresh_user(?int $id=null): ?array {
    $id=$id??($_SESSION['uid']??null); if(!$id) return null;
    $st=db()->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$id]); return $st->fetch()?:null;
}
function require_login(): array { $u=user(); if(!$u){flash('error','Please sign in.');redirect('login');} return $u; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['_csrf']??'')){http_response_code(403);die('Invalid CSRF token.');} }
function inr_paise(int $p): string {
    $r=(int)round($p/100);
    $s=(string)$r;
    if(strlen($s)>3){$pre=substr($s,0,-3);$suf=substr($s,-3);if(strlen($pre)>2)$pre=preg_replace('/\B(?=(\d{2})+(?!\d))/',',',$pre);$s=$pre.','.$suf;}
    return '₹'.$s;
}

db();
