<?php
$servername = "localhost"; 
$username = "root";
$password = "";
$dbname = "burger palace";

// 1. Initial Connection to Ensure Database Exists
$init_conn = @mysqli_connect($servername, $username, $password);
if ($init_conn) {
    @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `" . $dbname . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_conn);
}

// 2. Connect to burger palace database
$conn = @mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 3. Auto-create users table if empty
$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (!$chk || mysqli_num_rows($chk) == 0) {
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `fullname` VARCHAR(100) NOT NULL,
            `email` VARCHAR(100) NOT NULL UNIQUE,
            `userPassword` VARCHAR(255) NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed default User accounts
    @mysqli_query($conn, "INSERT INTO `users` (`id`, `fullname`, `email`, `userPassword`) VALUES
        (1, 'Admin', 'admin@burgerpalace.com', 'admin'),
        (2, 'rix4uni', 'rix4uni@gmail.com', 'admin')
        ON DUPLICATE KEY UPDATE `userPassword`=VALUES(`userPassword`);");
}
?>
