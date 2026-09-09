<?php
declare(strict_types=1);
session_start();
const DB_HOST='127.0.0.1'; const DB_PORT='3306'; const DB_NAME='partnerhub'; const DB_USER='root'; const DB_PASS='';
const SITE_NAME='PartnerHub'; const ADMIN_EMAIL='admin@partnerhub.local'; const ADMIN_PASSWORD='admin';
function db():PDO{
 static $p=null;if($p)return $p;
 $p=new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $p->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");$p->exec("USE `".DB_NAME."`");
 $p->exec("CREATE TABLE IF NOT EXISTS applications(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, application_no VARCHAR(30) UNIQUE NOT NULL,
 company_name VARCHAR(180) NOT NULL, website VARCHAR(255), contact_name VARCHAR(150) NOT NULL,
 email VARCHAR(190) NOT NULL, phone VARCHAR(60), country VARCHAR(100), company_size VARCHAR(60),
 partner_type VARCHAR(80) NOT NULL, regions VARCHAR(255), services TEXT, experience TEXT, goals TEXT,
 status ENUM('pending','reviewing','more_info','approved','declined') NOT NULL DEFAULT 'pending',
 admin_note TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB");
 $p->exec("CREATE TABLE IF NOT EXISTS application_events(
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, application_id INT UNSIGNED NOT NULL, event_text VARCHAR(255) NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB");
 $p->exec("CREATE TABLE IF NOT EXISTS admins(id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,email VARCHAR(190) UNIQUE NOT NULL,password_hash VARCHAR(255) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
 $s=$p->prepare("SELECT id FROM admins WHERE email=?");$s->execute([ADMIN_EMAIL]);
 if(!$s->fetch()){$i=$p->prepare("INSERT INTO admins(email,password_hash) VALUES(?,?)");$i->execute([ADMIN_EMAIL,password_hash(ADMIN_PASSWORD,PASSWORD_DEFAULT)]);}
 return $p;
}
function e($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function csrf():string{if(empty($_SESSION['csrf']))$_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf'];}
function verify_csrf():void{if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')){http_response_code(419);exit('Invalid request.');}}
function admin_logged():bool{return !empty($_SESSION['admin_id']);}
function require_admin():void{if(!admin_logged()){header('Location: login.php');exit;}}
function app_no():string{return 'PH-'.strtoupper(substr(bin2hex(random_bytes(5)),0,8));}
function flash($m=null){if($m!==null){$_SESSION['flash']=$m;return;} $x=$_SESSION['flash']??null;unset($_SESSION['flash']);return $x;}
db();