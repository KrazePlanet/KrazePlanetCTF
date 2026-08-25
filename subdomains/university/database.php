<?php
$hostname = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$dbuser = "root";
$dbPassword = "";
$dbname = "university_portal";

// 1. Initial connection to ensure database exists
$init_conn = @mysqli_connect($hostname, $dbuser, $dbPassword);
if ($init_conn) {
    @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `" . $dbname . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_conn);
}

// 2. Connect to university_portal database
$conn = @mysqli_connect($hostname, $dbuser, $dbPassword, $dbname);

if (!$conn) {
    die("Database Connection failed: " . mysqli_connect_error());
}

// 3. Auto-create tables if empty
$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'admin'");
if (!$chk || mysqli_num_rows($chk) == 0) {

    // Admin Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `admin` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `admin_id` varchar(50) NOT NULL UNIQUE,
          `password` varchar(255) NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Contact Inquiries Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `contact` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` varchar(100) NOT NULL,
          `email` varchar(100) NOT NULL,
          `message` text NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
          `is_hidden` tinyint(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Users / Students Table
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `full_name` varchar(100) NOT NULL,
          `email` varchar(100) NOT NULL UNIQUE,
          `password` varchar(255) NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
          `is_disabled` tinyint(1) DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed default Admin: admin / admin
    $admin_pwd = password_hash('admin', PASSWORD_DEFAULT);
    @mysqli_query($conn, "INSERT INTO `admin` (`id`, `admin_id`, `password`) VALUES (1, 'admin', '{$admin_pwd}') ON DUPLICATE KEY UPDATE `password`='{$admin_pwd}';");

    // Seed default Student user: student@university.edu / admin
    @mysqli_query($conn, "INSERT INTO `users` (`id`, `full_name`, `email`, `password`) VALUES (1, 'Alex Student', 'student@university.edu', '{$admin_pwd}') ON DUPLICATE KEY UPDATE `password`='{$admin_pwd}';");

    // Seed sample contact messages
    @mysqli_query($conn, "INSERT INTO `contact` (`name`, `email`, `message`) VALUES 
        ('Julian Vance', 'julian@example.com', 'Inquiry regarding Masters in Computer Science scholarship details.'),
        ('Elena Rostova', 'elena@example.com', 'Campus tour schedule inquiry for next semester.');");
} else {
    // Ensure admin user password 'admin' is up to date
    $admin_pwd = password_hash('admin', PASSWORD_DEFAULT);
    @mysqli_query($conn, "INSERT INTO `admin` (`id`, `admin_id`, `password`) VALUES (1, 'admin', '{$admin_pwd}') ON DUPLICATE KEY UPDATE `password`='{$admin_pwd}';");
}
?>
