<?php
// AccountHub — SaaS Platform Training Lab
// Intentional vulnerability: POST /account/update iterates over all POST fields
// and applies them to UPDATE users SET — no field whitelist. Passing is_admin=1
// or plan=enterprise elevates privileges via mass assignment.
const BASE     = '/subdomains/accounthub';
const LAB_FLAG = 'KP{mass_assignment_privilege_escalation}';

const PLANS = [
    'free'       => ['label'=>'Free',       'color'=>'#6b7280','projects'=>3, 'api_keys'=>1, 'storage'=>'1 GB', 'support'=>'Community'],
    'pro'        => ['label'=>'Pro',        'color'=>'#6366f1','projects'=>25,'api_keys'=>5, 'storage'=>'10 GB','support'=>'Email'],
    'enterprise' => ['label'=>'Enterprise', 'color'=>'#f59e0b','projects'=>999,'api_keys'=>50,'storage'=>'1 TB', 'support'=>'24/7 Dedicated'],
];

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('ACCOUNTHUBSID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='accounthub_lab';
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
        foreach(['projects','users'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        plan ENUM('free','pro','enterprise') DEFAULT 'free',
        is_admin TINYINT(1) DEFAULT 0,
        admin_self_granted TINYINT(1) DEFAULT 0,
        timezone VARCHAR(50) DEFAULT 'UTC',
        api_calls_today INT DEFAULT 0,
        api_calls_month INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        status ENUM('active','paused','archived') DEFAULT 'active',
        api_calls INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id))");

    if((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0){
        $seed=[
            ['Platform Admin','admin@accounthub.io','admin123','enterprise',1,0,'Asia/Kolkata',284750,5420310],
            ['Pro User','pro@accounthub.io','pro123','pro',0,0,'America/New_York',12048,351240],
            ['Student','student@accounthub.lab','student123','free',0,0,'Asia/Kolkata',148,4230],
        ];
        $ui=$pdo->prepare("INSERT INTO users(name,email,password_hash,plan,is_admin,admin_self_granted,timezone,api_calls_today,api_calls_month) VALUES(?,?,?,?,?,?,?,?,?)");
        foreach($seed as $u)
            $ui->execute([$u[0],$u[1],password_hash($u[2],PASSWORD_DEFAULT),$u[3],$u[4],$u[5],$u[6],$u[7],$u[8]]);

        $ids=[];
        foreach(['admin@accounthub.io','pro@accounthub.io','student@accounthub.lab'] as $e)
            $ids[]=(int)$pdo->query("SELECT id FROM users WHERE email='$e'")->fetchColumn();
        [$admin,$pro,$student]=$ids;

        $projs=[
            [$admin,'E-Commerce Platform','active',284750],
            [$admin,'Analytics Pipeline','active',125040],
            [$pro,'SaaS Dashboard','active',12048],
            [$pro,'Mobile App Backend','paused',3200],
            [$student,'My First Project','active',148],
        ];
        $pi=$pdo->prepare("INSERT INTO projects(user_id,name,status,api_calls) VALUES(?,?,?,?)");
        foreach($projs as $p) $pi->execute($p);
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
function fresh_user(): ?array {
    if(empty($_SESSION['uid'])) return null;
    $st=db()->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$_SESSION['uid']]); return $st->fetch()?:null;
}
function require_login(): array { $u=user(); if(!$u){flash('error','Please sign in.');redirect('login');} return $u; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['_csrf']??'')){http_response_code(403);die('Invalid CSRF token.');} }

function plan_badge(string $plan): string {
    $p=PLANS[$plan]??PLANS['free'];
    return '<span style="background:'.h($p['color']).'22;color:'.h($p['color']).';border:1px solid '.h($p['color']).'44;padding:3px 9px;border-radius:5px;font-size:.7rem;font-weight:800">'.h($p['label']).'</span>';
}

db();
