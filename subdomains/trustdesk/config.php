<?php
declare(strict_types=1);
session_start();

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'trustdesk';
const DB_USER = 'root';
const DB_PASS = '';
const SITE_NAME = 'TrustDesk';
const ADMIN_EMAIL = 'admin@trustdesk.local';
const ADMIN_PASSWORD = 'admin';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = 'mysql:host='.DB_HOST.';port='.DB_PORT.';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `".DB_NAME."`");

    $pdo->exec("CREATE TABLE IF NOT EXISTS reports (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        case_no VARCHAR(30) NOT NULL UNIQUE,
        reporter_name VARCHAR(120) NULL,
        reporter_email VARCHAR(190) NULL,
        anonymous TINYINT(1) NOT NULL DEFAULT 0,
        report_type VARCHAR(60) NOT NULL,
        target_url VARCHAR(500) NULL,
        target_username VARCHAR(120) NULL,
        title VARCHAR(220) NOT NULL,
        description TEXT NOT NULL,
        evidence TEXT NULL,
        severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
        status ENUM('new','investigating','action_required','resolved','closed') NOT NULL DEFAULT 'new',
        admin_response TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS report_notes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        report_id INT UNSIGNED NOT NULL,
        note TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS report_events (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        report_id INT UNSIGNED NOT NULL,
        event_text VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (report_id) REFERENCES reports(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    $check = $pdo->prepare("SELECT id FROM admins WHERE email=?");
    $check->execute([ADMIN_EMAIL]);
    if (!$check->fetch()) {
        $ins = $pdo->prepare("INSERT INTO admins(email,password_hash) VALUES(?,?)");
        $ins->execute([ADMIN_EMAIL, password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT)]);
    }
    return $pdo;
}

function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Invalid request.');
    }
}
function admin_logged(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void { if (!admin_logged()) { header('Location: login.php'); exit; } }
function case_no(): string { return 'TD-'.strtoupper(substr(bin2hex(random_bytes(5)),0,8)); }
function flash(?string $message=null): ?string {
    if ($message !== null) { $_SESSION['flash']=$message; return null; }
    $m=$_SESSION['flash']??null; unset($_SESSION['flash']); return $m;
}
db();
?>