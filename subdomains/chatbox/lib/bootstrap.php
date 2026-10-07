<?php
// ChatBox — Private Messaging App Training Lab
// Intentional vulnerability: GET /inbox/thread/<id> fetches all messages in the thread
// using only WHERE thread_id=? — it does NOT check if the session user is a participant.
// Any authenticated user can read private message threads by guessing thread IDs.
const BASE     = '/subdomains/chatbox';
const LAB_FLAG = 'KP{idor_private_conversation_access}';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('CHATBOXSID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='chatbox_lab';
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
        foreach(['messages','thread_participants','threads','users'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        avatar VARCHAR(8) DEFAULT '👤',
        status ENUM('online','offline','away') DEFAULT 'offline',
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS threads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        is_group TINYINT(1) DEFAULT 0,
        group_name VARCHAR(100) DEFAULT NULL,
        snooped_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS thread_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thread_id INT NOT NULL,
        user_id INT NOT NULL,
        UNIQUE KEY uq_tp(thread_id,user_id),
        FOREIGN KEY(thread_id) REFERENCES threads(id),
        FOREIGN KEY(user_id) REFERENCES users(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thread_id INT NOT NULL,
        sender_id INT NOT NULL,
        body TEXT NOT NULL,
        sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(thread_id) REFERENCES threads(id),
        FOREIGN KEY(sender_id) REFERENCES users(id))");

    if((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0){
        $seed_users=[
            ['Alice Chen','alice','alice@chatbox.app','alice123','👩‍💻','online'],
            ['Bob Kumar','bob','bob@chatbox.app','bob123','👨‍💼','offline'],
            ['Charlie Ray','charlie','charlie@chatbox.app','charlie123','🧑‍🎨','away'],
            ['Student','student','student@chatbox.lab','student123','🎓','online'],
        ];
        $ui=$pdo->prepare("INSERT INTO users(name,username,email,password_hash,avatar,status) VALUES(?,?,?,?,?,?)");
        foreach($seed_users as $u)
            $ui->execute([$u[0],$u[1],$u[2],password_hash($u[3],PASSWORD_DEFAULT),$u[4],$u[5]]);

        $ids=[];
        foreach(['alice@chatbox.app','bob@chatbox.app','charlie@chatbox.app','student@chatbox.lab'] as $e)
            $ids[]=(int)$pdo->query("SELECT id FROM users WHERE email='$e'")->fetchColumn();
        [$alice,$bob,$charlie,$student]=$ids;

        // Thread 1: Alice ↔ Bob (PRIVATE — contains sensitive financial messages)
        $pdo->exec("INSERT INTO threads(id,is_group) VALUES(1,0)");
        $pdo->prepare("INSERT INTO thread_participants(thread_id,user_id) VALUES(1,?)")->execute([$alice]);
        $pdo->prepare("INSERT INTO thread_participants(thread_id,user_id) VALUES(1,?)")->execute([$bob]);
        $msgs1=[
            [$alice,1,"Hey Bob, the wire transfer of ₹8,50,000 to the offshore account — did you confirm with the finance team?"],
            [$bob,1,"Yes, confirmed. Account number is 9912-XXXX-4407 at First National. Transfer code: FNT-2024-9918."],
            [$alice,1,"Perfect. And the acquisition deal — we're not announcing until next quarter. Keep it strictly between us."],
            [$bob,1,"Understood. Also, my login for the internal payroll system is bob_admin / b@nk3r2024 — I'll change it after the restructuring."],
            [$alice,1,"Thanks. One more thing — the merger documents are under /private/docs/merger_Q4_2024.pdf on the shared drive."],
            [$bob,1,"Got it. This conversation stays private. Let's not discuss further on any other channel."],
        ];
        $mi=$pdo->prepare("INSERT INTO messages(thread_id,sender_id,body) VALUES(?,?,?)");
        foreach($msgs1 as $m) $mi->execute($m);

        // Thread 2: Alice ↔ Charlie (design collaboration)
        $pdo->exec("INSERT INTO threads(id,is_group) VALUES(2,0)");
        $pdo->prepare("INSERT INTO thread_participants(thread_id,user_id) VALUES(2,?)")->execute([$alice]);
        $pdo->prepare("INSERT INTO thread_participants(thread_id,user_id) VALUES(2,?)")->execute([$charlie]);
        $msgs2=[
            [$alice,2,"Charlie! Are the new brand assets ready for the product launch?"],
            [$charlie,2,"Almost done! The logo variants and colour palette are finalized. Will send by EOD."],
            [$alice,2,"Brilliant. Remember the launch is confidential until Monday — NDA applies."],
            [$charlie,2,"Noted! Super excited for this one 🎨"],
        ];
        foreach($msgs2 as $m) $mi->execute($m);

        // Thread 3: Student ↔ Charlie (normal chat — the attacker's own thread)
        $pdo->exec("INSERT INTO threads(id,is_group) VALUES(3,0)");
        $pdo->prepare("INSERT INTO thread_participants(thread_id,user_id) VALUES(3,?)")->execute([$student]);
        $pdo->prepare("INSERT INTO thread_participants(thread_id,user_id) VALUES(3,?)")->execute([$charlie]);
        $msgs3=[
            [$charlie,3,"Hey Student, welcome to ChatBox!"],
            [$student,3,"Thanks Charlie! Excited to try this out."],
            [$charlie,3,"Let me know if you need anything 😊"],
        ];
        foreach($msgs3 as $m) $mi->execute($m);
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
function require_login(): array { $u=user(); if(!$u){flash('error','Please sign in.');redirect('login');} return $u; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function verify_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['_csrf']??'')){http_response_code(403);die('Invalid CSRF token.');} }
function time_ago(string $ts): string {
    $d=time()-strtotime($ts);
    if($d<60) return 'just now';
    if($d<3600) return floor($d/60).'m ago';
    if($d<86400) return floor($d/3600).'h ago';
    return date('d M',strtotime($ts));
}

db();
