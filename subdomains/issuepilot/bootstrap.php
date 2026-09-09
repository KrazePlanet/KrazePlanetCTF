<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    $server = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS bug_reports (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(180) NOT NULL, description TEXT NOT NULL, steps TEXT NOT NULL, expected_behavior TEXT NOT NULL, actual_behavior TEXT NOT NULL, severity ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium', status ENUM('new','triaged','in_progress','resolved','closed') NOT NULL DEFAULT 'new', reporter_name VARCHAR(120) NULL, reporter_email VARCHAR(190) NULL, page_url VARCHAR(500) NULL, browser_device VARCHAR(255) NULL, attachment VARCHAR(255) NULL, admin_note TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX(status), INDEX(severity), INDEX(created_at)) ENGINE=InnoDB");
    $check = $pdo->query('SELECT COUNT(*) c FROM admins')->fetch()['c'];
    if ((int)$check === 0) {
        $stmt = $pdo->prepare('INSERT INTO admins (email,password_hash) VALUES (?,?)');
        $stmt->execute([ADMIN_EMAIL, password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT)]);
    }
    return $pdo;
}
function e(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function csrf(): string { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid request token.'); } }
function admin_logged_in(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void { if (!admin_logged_in()) { header('Location: login.php'); exit; } }
function flash(?string $message = null): ?string { if ($message !== null) { $_SESSION['flash'] = $message; return null; } $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m; }

db();
