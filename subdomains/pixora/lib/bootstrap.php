<?php
// Pixora — Social Profile Platform Training Lab
// Intentional vulnerability: POST /profile/avatar trusts user_id from request body,
// not from the authenticated session — classic IDOR on profile picture update.
const BASE     = '/subdomains/pixora';
const LAB_FLAG = 'KP{idor_profile_picture_update}';

const AVATARS = ['🐱','🐻','🦊','🐼','🦁','🐸','🦄','🐺','🐯','🦋','🌟','🔥','👾','🎭','🌈','💎'];

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('PIXORASID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='pixora_lab';
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
        foreach(['likes','posts','users'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        bio VARCHAR(255) DEFAULT '',
        avatar VARCHAR(8) DEFAULT '🐱',
        avatar_changed_by INT NULL,
        followers INT DEFAULT 0,
        following INT DEFAULT 0,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        image_emoji VARCHAR(8) DEFAULT '🖼️',
        likes INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL, post_id INT NOT NULL,
        UNIQUE KEY uq_lp(user_id,post_id),
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(post_id) REFERENCES posts(id))");

    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0) {
        $seed_users=[
            ['Pixora Admin','pixora_official','admin@pixora.app','admin123','🌟 The official Pixora account. Sharing tips, updates & featured creators.','🌟',8420,1200,1],
            ['Alice Dev','alice_dev','alice@pixora.app','alice123','Full-stack developer 💻 | Open source contributor | Coffee addict ☕ | Building cool stuff on the internet.','🐱',3218,412,0],
            ['Bob Creative','bob_visuals','bob@pixora.app','bob123','Visual storyteller 🎨 | Photographer | UI/UX designer | Available for freelance work.','🎭',1894,289,0],
            ['Student','you','student@pixora.lab','student123','Just exploring Pixora 👀','🦊',12,38,0],
        ];
        $ui=$pdo->prepare("INSERT INTO users(name,username,email,password_hash,bio,avatar,followers,following,is_admin) VALUES(?,?,?,?,?,?,?,?,?)");
        foreach($seed_users as $u)
            $ui->execute([$u[0],$u[1],$u[2],password_hash($u[3],PASSWORD_DEFAULT),$u[4],$u[5],$u[6],$u[7],$u[8]]);

        $ids=[];
        foreach(['admin@pixora.app','alice@pixora.app','bob@pixora.app','student@pixora.lab'] as $e)
            $ids[]=(int)$pdo->query("SELECT id FROM users WHERE email='$e'")->fetchColumn();
        [$admin,$alice,$bob,$student]=$ids;

        $seed_posts=[
            [$admin,'🎉 Welcome to Pixora! Update your profile picture to personalize your account. Head to Settings → Edit Profile to get started.','🎊',412],
            [$admin,'📢 Pro tip: Your profile picture is the first thing people see. Make it count! Choose from our avatar collection in your profile settings.','💡',287],
            [$admin,'🔒 Security reminder: Your account settings are private to you. Only you can change your profile picture. Stay safe on Pixora!','🛡️',534],
            [$alice,'Just shipped a new open source project! Check it out — a lightweight PHP router that handles path parameters cleanly. Link in bio 🚀','💻',198],
            [$alice,'Hot take: session-based auth is underrated. Stateless tokens are fine but sometimes you just want to know WHO is making the request on the server side 🤔','🔑',143],
            [$alice,'Fun bug I found today: an app was accepting `user_id` from POST body to update profile data. Server never checked if it matched the session. Classic IDOR. Please validate server-side, folks! 🙏','🐛',312],
            [$bob,'New portfolio shots from last weekend 📸 The golden hour lighting was absolutely perfect. Sometimes you just have to wait for the right moment.','🌅',267],
            [$bob,'Working on a rebrand for a local café. Brand identity is so much more than a logo — it\'s the whole story ✨','🎨',189],
            [$bob,'Changed my profile picture again 😅 Can\'t decide between the bear or the owl. What do you think?','🐻',94],
            [$student,'Hello Pixora! New here 👋','🌱',3],
        ];
        $pi=$pdo->prepare("INSERT INTO posts(user_id,content,image_emoji,likes) VALUES(?,?,?,?)");
        foreach($seed_posts as $p) $pi->execute($p);
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
function time_ago(string $ts): string {
    $diff=time()-strtotime($ts);
    if($diff<60) return 'just now';
    if($diff<3600) return floor($diff/60).'m ago';
    if($diff<86400) return floor($diff/3600).'h ago';
    return date('d M Y',strtotime($ts));
}

db();
