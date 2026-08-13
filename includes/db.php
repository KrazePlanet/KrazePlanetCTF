<?php
// includes/db.php - Resilient Database Connection & Automatic Initialization

$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "vexiumctf";

// Include existing config if present
if (file_exists(__DIR__ . '/../config/database.php')) {
    include_once __DIR__ . '/../config/database.php';
}

$pdo = null;
$db_error = null;

// Try combinations of hosts & common default passwords for local XAMPP/LAMPP
$hosts = array_unique([$db_host, "127.0.0.1", "localhost"]);
$passwords = array_unique([$db_pass, "", "secret123", "root"]);

$connected = false;
foreach ($hosts as $host) {
    foreach ($passwords as $pass) {
        try {
            // 1. Connect without DB selected to ensure database exists
            $pdo_init = new PDO("mysql:host=$host;charset=utf8mb4", $db_user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 2
            ]);
            
            $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // 2. Connect to the specific database
            $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $connected = true;
            break 2;
        } catch (PDOException $e) {
            $db_error = $e->getMessage();
        }
    }
}

if ($pdo && $connected) {
    // 3. Create tables if they do not exist yet
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `username` VARCHAR(50) NOT NULL UNIQUE,
              `email` VARCHAR(100) NOT NULL UNIQUE,
              `password` VARCHAR(255) NOT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `user_solved_labs` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `lab_id` VARCHAR(255) NOT NULL,
              `solved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY `user_lab_unique` (`user_id`, `lab_id`),
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS `user_bookmarks` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `lab_id` VARCHAR(255) NOT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY `user_bookmark_unique` (`user_id`, `lab_id`),
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}
