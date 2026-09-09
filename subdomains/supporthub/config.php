<?php
declare(strict_types=1);
session_start();

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'supporthub';
const DB_USER = 'root';
const DB_PASS = '';

const SITE_NAME = 'SupportHub';
const ADMIN_EMAIL = 'admin@supporthub.local';
const ADMIN_PASSWORD = 'admin';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";charset=utf8mb4";
    $server = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $server->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ticket_no VARCHAR(30) UNIQUE NOT NULL,
        name VARCHAR(120) NOT NULL,
        email VARCHAR(190) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        category VARCHAR(80) NOT NULL,
        priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
        message TEXT NOT NULL,
        status ENUM('open','pending','resolved','closed') NOT NULL DEFAULT 'open',
        admin_note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email=? LIMIT 1");
    $stmt->execute([ADMIN_EMAIL]);
    if (!$stmt->fetch()) {
        $ins = $pdo->prepare("INSERT INTO admins(email,password_hash) VALUES(?,?)");
        $ins->execute([ADMIN_EMAIL, password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT)]);
    }
    return $pdo;
}
db();

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419); exit('Invalid request.');
    }
}
function flash(?string $message = null): ?string {
    if ($message !== null) { $_SESSION['flash'] = $message; return null; }
    $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m;
}
function ticket_no(): string { return 'SH-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(3))); }
function admin_required(): void {
    if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
}
