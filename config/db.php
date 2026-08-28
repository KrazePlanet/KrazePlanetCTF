<?php
// config/db.php - Resilient, High-Performance Database Connection

if (file_exists(__DIR__ . '/database.php')) {
    include_once __DIR__ . '/database.php';
}

$db_host = isset($db_host) && !empty($db_host) ? $db_host : (getenv("DB_HOST") ?: "127.0.0.1");
$db_user = isset($db_user) && !empty($db_user) ? $db_user : (getenv("DB_USER") ?: "root");
$db_pass = isset($db_pass) ? $db_pass : (getenv("DB_PASS") !== false ? getenv("DB_PASS") : "");
$db_name = isset($db_name) && !empty($db_name) ? $db_name : (getenv("DB_NAME") ?: "KrazePlanet");

$pdo = null;
$db_error = null;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 2,
];

// Connection targets without dbname (for auto-creating the database)
$base_targets = [
    "mysql:host={$db_host};charset=utf8mb4",
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;charset=utf8mb4",
    "mysql:host=localhost;charset=utf8mb4"
];

// Connection targets with dbname
$targets = [
    "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname={$db_name};charset=utf8mb4",
    "mysql:host=localhost;dbname={$db_name};charset=utf8mb4"
];

// Try connecting with the database name first
foreach ($targets as $dsn) {
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        $db_error = null;
        break;
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}

// If connection failed, try connecting without dbname and create the database
if (!$pdo && $db_error) {
    foreach ($base_targets as $dsn) {
        try {
            $temp_pdo = new PDO($dsn, $db_user, $db_pass, $options);
            $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $temp_pdo = null;
            // Now retry with the database
            foreach ($targets as $retry_dsn) {
                try {
                    $pdo = new PDO($retry_dsn, $db_user, $db_pass, $options);
                    $db_error = null;
                    break 2;
                } catch (PDOException $e) {
                    $db_error = $e->getMessage();
                }
            }
        } catch (PDOException $e) {
            $db_error = $e->getMessage();
        }
    }
}

// Quick 150ms retry if MariaDB was briefly restarting
if (!$pdo) {
    usleep(150000);
    foreach ($targets as $dsn) {
        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            $db_error = null;
            break;
        } catch (PDOException $e) {
            $db_error = $e->getMessage();
        }
    }
}

// Auto-create tables if connected successfully
if ($pdo) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(255) NOT NULL UNIQUE,
            `fullname` VARCHAR(255) DEFAULT NULL,
            `phone` VARCHAR(20) DEFAULT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `avatar` TEXT DEFAULT NULL,
            `role` VARCHAR(50) DEFAULT 'user',
            `country` VARCHAR(10) DEFAULT NULL,
            `otp` VARCHAR(10) DEFAULT NULL,
            `otp_expires` DATETIME DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `user_solved_labs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `lab_id` VARCHAR(255) NOT NULL,
            `difficulty` VARCHAR(20) DEFAULT 'easy',
            `points` INT DEFAULT 20,
            `solved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_user_lab_solved` (`user_id`, `lab_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `user_bookmarks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `lab_id` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_user_lab_bookmark` (`user_id`, `lab_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `user_lab_history` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `lab_id` VARCHAR(255) NOT NULL,
            `lab_title` VARCHAR(500) DEFAULT NULL,
            `lab_badge` VARCHAR(20) DEFAULT 'LAB',
            `lab_category` VARCHAR(100) DEFAULT 'Web Security',
            `lab_url` TEXT DEFAULT NULL,
            `last_accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_user_lab_history` (`user_id`, `lab_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `user_notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT DEFAULT NULL,
            `title` VARCHAR(255) DEFAULT NULL,
            `message` TEXT DEFAULT NULL,
            `type` VARCHAR(50) DEFAULT 'info',
            `is_read` TINYINT(1) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `lab_instances` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `lab_id` VARCHAR(255) NOT NULL,
            `instance_dir` VARCHAR(500) DEFAULT NULL,
            `db_name` VARCHAR(255) DEFAULT NULL,
            `status` VARCHAR(50) DEFAULT 'active',
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `tasks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(500) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `category_name` VARCHAR(100) DEFAULT 'General',
            `assigned_users` TEXT DEFAULT NULL,
            `submission_date` DATETIME DEFAULT NULL,
            `labs_json` TEXT DEFAULT NULL,
            `created_by` INT DEFAULT NULL,
            FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Table may already exist or other non-critical error
        }
    }

    // Auto-seed / ensure default admin account exists with username 'admin' and password 'admin'
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = 'admin' LIMIT 1");
        $stmt->execute();
        $adminUser = $stmt->fetch();
        $adminHashed = password_hash('admin', PASSWORD_DEFAULT);

        if (!$adminUser) {
            $ins = $pdo->prepare("INSERT INTO users (username, fullname, email, password, role) VALUES ('admin', 'Administrator', 'admin@krazeplanet.com', ?, 'admin')");
            $ins->execute([$adminHashed]);
        } else {
            if (!password_verify('admin', $adminUser['password']) && $adminUser['password'] !== 'admin') {
                $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->execute([$adminHashed, $adminUser['id']]);
            }
        }
    } catch (PDOException $e) {
        // Non-critical auto-seed error
    }
}
