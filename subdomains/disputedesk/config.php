<?php
declare(strict_types=1);
session_start();

const DB_HOST='127.0.0.1';
const DB_USER='root';
const DB_PASS='';
const DB_NAME='disputedesk';
const SITE_NAME='DisputeDesk';
const ADMIN_EMAIL='admin@disputedesk.local';
const ADMIN_PASSWORD='admin';

function h(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }
function db(): mysqli {
    static $db=null;
    if($db instanceof mysqli) return $db;
    mysqli_report(MYSQLI_REPORT_OFF);
    $db=new mysqli(DB_HOST,DB_USER,DB_PASS);
    if($db->connect_errno) die('MySQL connection failed. Start MySQL in XAMPP.');
    $db->set_charset('utf8mb4');
    if(!$db->query("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) die('Could not create database: '.h($db->error));
    $db->select_db(DB_NAME);

    $queries=[
    "CREATE TABLE IF NOT EXISTS disputes(
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      case_no VARCHAR(30) NOT NULL UNIQUE,
      claimant_name VARCHAR(120) NOT NULL,
      claimant_email VARCHAR(180) NOT NULL,
      phone VARCHAR(50) NOT NULL DEFAULT '',
      company VARCHAR(160) NOT NULL DEFAULT '',
      transaction_ref VARCHAR(100) NOT NULL DEFAULT '',
      amount DECIMAL(12,2) NOT NULL DEFAULT 0,
      currency VARCHAR(10) NOT NULL DEFAULT 'INR',
      dispute_type ENUM('Unauthorized payment','Duplicate charge','Product not received','Service issue','Not as described','Refund issue','Other') NOT NULL,
      description TEXT NOT NULL,
      desired_resolution VARCHAR(160) NOT NULL DEFAULT '',
      evidence_summary TEXT NOT NULL,
      status ENUM('submitted','under_review','awaiting_response','resolved','closed','rejected') NOT NULL DEFAULT 'submitted',
      priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
      admin_response TEXT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS evidence(
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      dispute_id INT UNSIGNED NOT NULL,
      original_name VARCHAR(255) NOT NULL,
      stored_name VARCHAR(255) NOT NULL,
      mime_type VARCHAR(100) NOT NULL,
      file_size INT UNSIGNED NOT NULL DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_evidence_dispute FOREIGN KEY(dispute_id) REFERENCES disputes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS dispute_events(
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      dispute_id INT UNSIGNED NOT NULL,
      event_text VARCHAR(255) NOT NULL,
      public_note TEXT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_event_dispute FOREIGN KEY(dispute_id) REFERENCES disputes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS admins(
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(180) NOT NULL UNIQUE,
      password_hash VARCHAR(255) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    foreach($queries as $q) if(!$db->query($q)) die('Database setup failed: '.h($db->error));

    $email=$db->real_escape_string(ADMIN_EMAIL);
    $r=$db->query("SELECT id FROM admins WHERE email='$email' LIMIT 1");
    if(!$r || !$r->num_rows){
        $hash=password_hash(ADMIN_PASSWORD,PASSWORD_DEFAULT);
        $st=$db->prepare("INSERT INTO admins(email,password_hash) VALUES(?,?)");
        $st->bind_param('ss',$email2,$hash); $email2=ADMIN_EMAIL; $st->execute();
    }
    return $db;
}
db();

function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(16)); return $_SESSION['csrf']; }
function check_csrf(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) die('Invalid request.'); }
function admin(): bool { return !empty($_SESSION['admin_id']); }
function need_admin(): void { if(!admin()){header('Location: login.php');exit;} }
function case_no(): string { return 'DD-'.strtoupper(bin2hex(random_bytes(4))); }
function label(string $s): string { return ucwords(str_replace('_',' ',$s)); }

function rows(mysqli_stmt $st): array {
    $meta=$st->result_metadata(); if(!$meta) return [];
    $fields=$meta->fetch_fields(); $row=[]; $refs=[];
    foreach($fields as $f){$row[$f->name]=null;$refs[]=&$row[$f->name];}
    call_user_func_array([$st,'bind_result'],$refs);
    $out=[]; while($st->fetch()){ $x=[]; foreach($row as $k=>$v)$x[$k]=$v; $out[]=$x; }
    $meta->free(); return $out;
}
function one(mysqli_stmt $st): ?array { $x=rows($st); return $x[0]??null; }
?>