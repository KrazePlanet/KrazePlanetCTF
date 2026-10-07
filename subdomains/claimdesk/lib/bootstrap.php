<?php
// ClaimDesk — Expense Claim Portal Training Lab
// Intentional vulnerability: claims table has no UNIQUE constraint on claim_ref.
// Submitting the same POST body multiple times creates duplicate approved claims.
const BASE     = '/subdomains/claimdesk';
const LAB_FLAG = 'KP{duplicate_claim_no_idempotency}';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('CLAIMDESKSID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='claimdesk_lab';
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
        foreach(['claims','users'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL, employee_id VARCHAR(20),
        department VARCHAR(80) DEFAULT 'Engineering',
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    // INTENTIONAL: claim_ref is VARCHAR with NO UNIQUE constraint — this IS the vulnerability.
    // A proper implementation would add: UNIQUE KEY uq_ref (user_id, claim_ref)
    // Without it, the same claim_ref can be inserted multiple times.
    $pdo->exec("CREATE TABLE IF NOT EXISTS claims (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        claim_ref VARCHAR(64) NOT NULL,
        category VARCHAR(60) NOT NULL,
        amount INT NOT NULL,
        description VARCHAR(500) NOT NULL,
        receipt_note VARCHAR(255) DEFAULT '',
        travel_from VARCHAR(100) DEFAULT '',
        travel_to VARCHAR(100) DEFAULT '',
        travel_date DATE NULL,
        status ENUM('submitted','under_review','approved','rejected','paid') DEFAULT 'submitted',
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME NULL,
        FOREIGN KEY(user_id) REFERENCES users(id))");

    /* SEED USERS */
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0) {
        foreach([
            ['Finance Admin','admin@claimdesk.app','admin123','EMP001','Finance',1],
            ['Ananya Krishnan','ananya@example.com','ananya123','EMP042','Sales',0],
            ['Demo Student','student@claimdesk.lab','student123','EMP099','Engineering',0],
        ] as $u)
            $pdo->prepare("INSERT INTO users(name,email,password_hash,employee_id,department,is_admin) VALUES(?,?,?,?,?,?)")
                ->execute([$u[0],$u[1],password_hash($u[2],PASSWORD_DEFAULT),$u[3],$u[4],$u[5]]);

        // Pre-seed some claims for context
        $ananya_id=(int)$pdo->query("SELECT id FROM users WHERE email='ananya@example.com'")->fetchColumn();
        $student_id=(int)$pdo->query("SELECT id FROM users WHERE email='student@claimdesk.lab'")->fetchColumn();

        foreach([
            [$ananya_id,gen_ref(),'Travel',450000,'Flight to Mumbai for client meeting','IndiGo PNR: 6E-4821','Mumbai','Delhi','2026-09-10','paid'],
            [$ananya_id,gen_ref(),'Accommodation',850000,'Hotel stay — 2 nights Trident BKC','Bill #TBK2024089','','',null,'approved'],
            [$ananya_id,gen_ref(),'Meals',85000,'Client dinner — The Table Mumbai','Receipt attached','','',null,'approved'],
            [$student_id,gen_ref(),'Equipment',230000,'USB-C hub and mechanical keyboard','Amazon order #402-8891023-1234567','','',null,'approved'],
            [$student_id,gen_ref(),'Training',499900,'AWS Solutions Architect exam fee','Pearson VUE confirmation #8821','','',null,'paid'],
        ] as $c)
            $pdo->prepare("INSERT INTO claims(user_id,claim_ref,category,amount,description,receipt_note,travel_from,travel_to,travel_date,status) VALUES(?,?,?,?,?,?,?,?,?,?)")
                ->execute($c);
    }
}

function gen_ref(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
}

/* ── HELPERS ──────────────────────────────────────────────── */
function h($s): string { return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8'); }
function url(string $p=''): string { return BASE.($p?'/'.ltrim($p,'/'):''); }
function inr(int $paise): string {
    $s=(string)abs($paise);
    $dec=substr($s,-2); $whole=substr($s,0,-2)?:'0';
    if(strlen($whole)>3){
        $pre=substr($whole,0,-3); $suf=substr($whole,-3);
        if(strlen($pre)>2) $pre=preg_replace('/\B(?=(\d{2})+(?!\d))/',',',$pre);
        $whole=$pre.','.$suf;
    }
    return ($paise<0?'−':'').'₹'.$whole.'.'.$dec;
}
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

function status_tag(string $s): string {
    return match($s){
        'approved','paid'=>'tag-success', 'rejected'=>'tag-danger',
        'under_review'=>'tag-warning', default=>'tag-info'
    };
}
function status_icon(string $s): string {
    return match($s){'approved'=>'✓','paid'=>'💰','rejected'=>'✗','under_review'=>'⏳',default=>'📋'};
}
function category_icon(string $c): string {
    return match($c){'Travel'=>'✈️','Accommodation'=>'🏨','Meals'=>'🍽️','Equipment'=>'💻','Office Supplies'=>'📎','Training'=>'🎓','Medical'=>'🏥',default=>'📋'};
}

db();
