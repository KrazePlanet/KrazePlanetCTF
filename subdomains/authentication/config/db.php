<?php 
// Enable us to use Headers
ob_start();

// Set sessions
if(!isset($_SESSION)) {
    session_start();
}

$hostname = "localhost";
$username = "root";
$password = "";
$dbname   = "php8_mysql_authentication";

// 1. Initial Connection to Ensure Database Exists
$init_conn = @mysqli_connect($hostname, $username, $password);
if ($init_conn) {
    @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `" . $dbname . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_conn);
}

// 2. Connect to php8_mysql_authentication database
$connection = @mysqli_connect($hostname, $username, $password, $dbname);

if (!$connection) {
    die("Database connection not established: " . mysqli_connect_error());
}

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @mysqli_query($connection, "SHOW TABLES LIKE 'users'");
if (!$chk || mysqli_num_rows($chk) == 0) {

    // Table: users
    @mysqli_query($connection, "
        CREATE TABLE IF NOT EXISTS `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `firstname` varchar(100) NOT NULL,
          `lastname` varchar(100) NOT NULL,
          `email` varchar(100) NOT NULL UNIQUE,
          `mobilenumber` varchar(20) NOT NULL,
          `password` varchar(255) NOT NULL,
          `token` varchar(255) NOT NULL,
          `is_active` enum('0','1') NOT NULL DEFAULT '1',
          `date_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed Verified Demo Accounts
    // Passwords meeting regex: Has digit, special char (@), uppercase, lowercase, 8-20 chars: Admin@12345
    $admin_pwd = password_hash('Admin@12345', PASSWORD_BCRYPT);
    $user_pwd  = password_hash('User@12345', PASSWORD_BCRYPT);
    $token1 = md5(uniqid('admin', true));
    $token2 = md5(uniqid('user', true));

    @mysqli_query($connection, "INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `mobilenumber`, `password`, `token`, `is_active`, `date_time`) VALUES
        (1, 'Admin', 'User', 'admin@gmail.com', '9876543210', '{$admin_pwd}', '{$token1}', '1', NOW()),
        (2, 'Jane', 'Doe', 'user@gmail.com', '9876543211', '{$user_pwd}', '{$token2}', '1', NOW())
        ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");
}
?>
