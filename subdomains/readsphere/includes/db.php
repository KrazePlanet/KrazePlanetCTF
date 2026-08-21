<?php
/**
 * Database connection and auto-provisioning for ReadSphere
 */

if (!defined('DB_HOST')) define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', $_ENV['DB_NAME'] ?? 'read_sphere_db');
if (!defined('DB_USER')) define('DB_USER', $_ENV['DB_USER'] ?? 'root');
if (!defined('DB_PASS')) define('DB_PASS', $_ENV['DB_PASS'] ?? '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// 1. Initial Connection to Ensure Database Exists
try {
    $init_pdo = new PDO(sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET), DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $init_pdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;', DB_NAME));
    unset($init_pdo);
} catch (PDOException $e) {
    // Ignore if permission denied for create database
}

// 2. Main PDO Connection
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_PERSISTENT         => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET time_zone = '+00:00'");
} catch (PDOException $e) {
    die('Database connection error: ' . $e->getMessage());
}

// 3. Auto-Provision Schema and Seed Data If Empty
try {
    $chk = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
    if ($chk === 0) {

        // Table: users
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `username` VARCHAR(50) NOT NULL UNIQUE,
              `email` VARCHAR(100) NOT NULL UNIQUE,
              `password` VARCHAR(255) NOT NULL,
              `role` ENUM('user', 'moderator', 'admin') DEFAULT 'user',
              `avatar` VARCHAR(255) DEFAULT 'default-avatar.png',
              `bio` TEXT DEFAULT NULL,
              `is_active` TINYINT(1) DEFAULT 1,
              `email_verified_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `verification_token` VARCHAR(100) DEFAULT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` DATETIME DEFAULT NULL,
              `last_login_at` DATETIME DEFAULT NULL,
              `last_login_ip` VARCHAR(45) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: books
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `books` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `title` VARCHAR(255) NOT NULL,
              `author` VARCHAR(255) NOT NULL,
              `genre` VARCHAR(100) DEFAULT 'General',
              `summary` TEXT NOT NULL,
              `liked` INT DEFAULT 0,
              `disliked` INT DEFAULT 0,
              `like_count` INT DEFAULT 0,
              `views` INT DEFAULT 0,
              `image` VARCHAR(255) DEFAULT 'default-book.png',
              `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
              `is_deleted` TINYINT(1) DEFAULT 0,
              `deleted_at` DATETIME DEFAULT NULL,
              `deleted_by` INT DEFAULT NULL,
              `delete_reason` TEXT DEFAULT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX (`user_id`),
              INDEX (`status`),
              INDEX (`genre`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: comments
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `comments` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `book_id` INT NOT NULL,
              `user_id` INT NOT NULL,
              `content` TEXT NOT NULL,
              `is_deleted` TINYINT(1) DEFAULT 0,
              `is_admin_deleted` TINYINT(1) DEFAULT 0,
              `deleted_at` DATETIME DEFAULT NULL,
              `deleted_by` INT DEFAULT NULL,
              `delete_reason` TEXT DEFAULT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX (`book_id`),
              INDEX (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: comment_likes
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `comment_likes` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `comment_id` INT NOT NULL,
              `user_id` INT NOT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY `unique_comment_like` (`comment_id`, `user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: comment_reports
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `comment_reports` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `comment_id` INT NOT NULL,
              `user_id` INT NOT NULL,
              `reason` TEXT NOT NULL,
              `status` ENUM('pending', 'resolved', 'rejected') DEFAULT 'pending',
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: book_likes
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `book_likes` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `book_id` INT NOT NULL,
              `user_id` INT NOT NULL,
              `is_like` TINYINT(1) DEFAULT 1,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY `unique_book_like` (`book_id`, `user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: user_tokens
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_tokens` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `token` VARCHAR(255) NOT NULL UNIQUE,
              `type` VARCHAR(50) NOT NULL,
              `expires_at` DATETIME NOT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `user_agent` TEXT DEFAULT NULL,
              `ip_address` VARCHAR(45) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: user_activities
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_activities` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `type` VARCHAR(50) NOT NULL,
              `description` TEXT NOT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: admin_logs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admin_logs` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `action` VARCHAR(100) NOT NULL,
              `details` TEXT DEFAULT NULL,
              `ip_address` VARCHAR(45) DEFAULT NULL,
              `user_agent` TEXT DEFAULT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: moderation_actions
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `moderation_actions` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `moderator_id` INT NOT NULL,
              `target_type` VARCHAR(50) NOT NULL,
              `target_id` INT NOT NULL,
              `action` VARCHAR(50) NOT NULL,
              `reason` TEXT DEFAULT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: login_attempts
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `login_attempts` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `email` VARCHAR(100) NOT NULL,
              `ip_address` VARCHAR(45) NOT NULL,
              `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: notifications
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `notifications` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `type` VARCHAR(50) NOT NULL,
              `message` TEXT NOT NULL,
              `link` VARCHAR(255) DEFAULT '',
              `target_url` VARCHAR(255) DEFAULT '',
              `is_read` TINYINT(1) DEFAULT 0,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: password_resets
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `password_resets` (
              `email` VARCHAR(100) NOT NULL PRIMARY KEY,
              `token` VARCHAR(255) NOT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Table: remember_me_tokens
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `remember_me_tokens` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `token` VARCHAR(255) NOT NULL,
              `expires_at` DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Seed Admin & Demo Users
        $admin_pwd = password_hash('admin', PASSWORD_DEFAULT);
        $user_pwd  = password_hash('password', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `is_active`, `email_verified_at`) VALUES
            (1, 'admin', 'admin@readsphere.com', ?, 'admin', 1, NOW()),
            (2, 'reader', 'reader@readsphere.com', ?, 'user', 1, NOW())
            ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");
        $stmt->execute([$admin_pwd, $user_pwd]);

        // Seed Sample Books
        $stmt_book = $pdo->prepare("INSERT INTO `books` (`id`, `user_id`, `title`, `author`, `genre`, `summary`, `views`, `like_count`, `status`) VALUES
            (1, 1, 'Dune', 'Frank Herbert', 'Science Fiction', 'Set on the desert planet Arrakis, Dune is the story of the boy Paul Atreides, heir to a noble family tasked with ruling an inhospitable world where the only valuable commodity is the spice melange.', 250, 48, 'approved'),
            (2, 1, 'Clean Code', 'Robert C. Martin', 'Technology', 'Even bad code can function. But if code is not clean, it can bring a development organization to its knees. Every year, countless hours and significant resources are lost because of poorly written code.', 180, 35, 'approved'),
            (3, 2, 'The Hobbit', 'J.R.R. Tolkien', 'Fantasy', 'Bilbo Baggins is a hobbit who enjoys a comfortable, unambitious life, rarely traveling any farther than his pantry or cellar. But his contentment is disturbed when the wizard Gandalf and a company of dwarves arrive on his doorstep.', 320, 62, 'approved'),
            (4, 2, '1984', 'George Orwell', 'Dystopian', 'Winston Smith toes the Party line, rewriting history to satisfy the Ministry of Truth. With each lie he writes, Winston grows to hate the Party that seeks power for its own sake and persecutes individual thought.', 410, 89, 'approved')
            ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);");
        $stmt_book->execute();

        // Seed Sample Comments
        $pdo->exec("INSERT INTO `comments` (`id`, `book_id`, `user_id`, `content`) VALUES
            (1, 1, 2, 'One of the greatest world-building masterpieces in literary history!'),
            (2, 2, 2, 'A timeless guide for writing maintainable and expressive software.'),
            (3, 3, 1, 'A heartwarming, thrilling adventure from start to finish.');");
    }
} catch (PDOException $e) {
    error_log("Schema auto-provisioning warning: " . $e->getMessage());
}

/**
 * Utility functions
 */
function redirect(string $path, int $statusCode = 302): void {
    if (headers_sent()) {
        echo sprintf('<script>window.location.href = %s;</script>', json_encode($path));
        exit();
    }
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0 || strpos($path, '/') === 0) {
        $url = $path;
    } else {
        $url = rtrim(BASE_PATH ?? '', '/') . '/' . ltrim($path, '/');
    }
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    header("Location: $url", true, $statusCode);
    exit();
}

function sanitize(mixed $data, bool $stripTags = true): mixed {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    $data = trim((string)$data);
    if ($stripTags) {
        $data = strip_tags($data);
    }
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function is_ajax_request(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function json_response(mixed $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function is_valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>
