<?php
// StaffHub — Corporate HR Portal Training Lab
// Intentional vulnerability: POST /hr/payroll/export only checks require_login(),
// not whether the user has the 'hr' role — Missing Function-Level Access Control.
const BASE     = '/subdomains/staffhub';
const LAB_FLAG = 'KP{missing_function_level_access_control}';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('STAFFHUBSID');
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host='127.0.0.1'; $user='root'; $pass=''; $name='staffhub_lab';
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
        foreach(['audit_log','leaves','payslips','users'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        employee_id VARCHAR(20) NOT NULL UNIQUE,
        department VARCHAR(80) NOT NULL,
        designation VARCHAR(80) NOT NULL,
        role ENUM('employee','manager','hr') DEFAULT 'employee',
        salary_inr INT NOT NULL DEFAULT 0,
        joining_date DATE NOT NULL,
        leave_balance INT DEFAULT 18,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payslips (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        month VARCHAR(20) NOT NULL,
        basic INT NOT NULL,
        hra INT NOT NULL,
        conveyance INT NOT NULL,
        special INT NOT NULL,
        pf_deduction INT NOT NULL,
        tds_deduction INT NOT NULL,
        net_pay INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS leaves (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        leave_type ENUM('casual','sick','earned','unpaid') NOT NULL,
        from_date DATE NOT NULL,
        to_date DATE NOT NULL,
        days INT NOT NULL,
        reason VARCHAR(255) NOT NULL,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        FOREIGN KEY(user_id) REFERENCES users(id))");

    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        detail TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id))");

    if((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()===0){
        $seed=[
            ['Meera Iyer','hr.admin@staffhub.in','hr123','EMP001','Human Resources','HR Manager','hr',185000,'2019-03-01',22],
            ['Priya Nair','manager.priya@staffhub.in','priya123','EMP002','Engineering','Engineering Manager','manager',210000,'2020-06-15',20],
            ['Rahul Desai','rahul.desai@staffhub.in','rahul123','EMP003','Engineering','Senior Software Engineer','employee',145000,'2021-08-10',18],
            ['Ananya Singh','ananya.singh@staffhub.in','ananya123','EMP004','Marketing','Marketing Executive','employee',95000,'2022-02-20',18],
            ['Student Intern','student@staffhub.lab','student123','EMP099','Engineering','Intern','employee',25000,'2024-07-01',6],
        ];
        $ui=$pdo->prepare("INSERT INTO users(name,email,password_hash,employee_id,department,designation,role,salary_inr,joining_date,leave_balance) VALUES(?,?,?,?,?,?,?,?,?,?)");
        foreach($seed as $u)
            $ui->execute([$u[0],$u[1],password_hash($u[2],PASSWORD_DEFAULT),$u[3],$u[4],$u[5],$u[6],$u[7],$u[8],$u[9]]);

        $ids=[];
        foreach(['hr.admin@staffhub.in','manager.priya@staffhub.in','rahul.desai@staffhub.in','ananya.singh@staffhub.in','student@staffhub.lab'] as $e)
            $ids[]=(int)$pdo->query("SELECT id FROM users WHERE email='$e'")->fetchColumn();
        [$hr,$mgr,$rahul,$ananya,$student]=$ids;

        // Payslips for October 2024
        $ps=[
            [$hr,  'October 2024',92500,33300,1600,10600,11100,9250,117650],
            [$mgr, 'October 2024',105000,37800,1600,14300,12600,10500,135600],
            [$rahul,'October 2024',72500,26100,1600,8925,8700,7250,93175],
            [$ananya,'October 2024',47500,17100,1600,5300,5700,4750,61050],
            [$student,'October 2024',12500,4500,800,1450,1500,1250,16500],
        ];
        $pi=$pdo->prepare("INSERT INTO payslips(user_id,month,basic,hra,conveyance,special,pf_deduction,tds_deduction,net_pay) VALUES(?,?,?,?,?,?,?,?,?)");
        foreach($ps as $p) $pi->execute($p);

        // A couple of leaves
        $pdo->prepare("INSERT INTO leaves(user_id,leave_type,from_date,to_date,days,reason,status) VALUES(?,?,?,?,?,?,?)")
            ->execute([$rahul,'casual','2024-10-14','2024-10-15',2,'Family function','approved']);
        $pdo->prepare("INSERT INTO leaves(user_id,leave_type,from_date,to_date,days,reason,status) VALUES(?,?,?,?,?,?,?)")
            ->execute([$student,'sick','2024-10-07','2024-10-07',1,'Fever','approved']);
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
function is_hr(): bool { $u=user(); return $u&&in_array($u['role'],['hr']); }
function is_manager(): bool { $u=user(); return $u&&in_array($u['role'],['hr','manager']); }
function inr(int $n): string {
    $s=(string)abs($n);
    if(strlen($s)>3){
        $pre=substr($s,0,-3); $suf=substr($s,-3);
        if(strlen($pre)>2) $pre=preg_replace('/\B(?=(\d{2})+(?!\d))/',',',$pre);
        $s=$pre.','.$suf;
    }
    return ($n<0?'−':'').'₹'.$s;
}

db();
