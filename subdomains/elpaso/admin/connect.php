<?php
$servername = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username = "root";
$password = "";
$basename = "elpaso";

// 1. Initial Connection to Ensure Database Exists
$init_conn = @mysqli_connect($servername, $username, $password);
if ($init_conn) {
    @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `" . $basename . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_conn);
}

// 2. Connect to elpaso database
$dbc = @mysqli_connect($servername, $username, $password, $basename);

if (!$dbc) {
    die('Error connecting to MySQL server: ' . mysqli_connect_error());
}

mysqli_set_charset($dbc, "utf8mb4");

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @mysqli_query($dbc, "SHOW TABLES LIKE 'admin'");
if (!$chk || mysqli_num_rows($chk) == 0) {

    // Table: admin
    mysqli_query($dbc, "
        CREATE TABLE IF NOT EXISTS `admin` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` varchar(255) NOT NULL,
          `username` varchar(255) NOT NULL UNIQUE,
          `password` varchar(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: reservations_pending
    mysqli_query($dbc, "
        CREATE TABLE IF NOT EXISTS `reservations_pending` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` varchar(255) NOT NULL,
          `mail` varchar(255) NOT NULL,
          `phone` varchar(50) NOT NULL,
          `visitors` int(11) NOT NULL DEFAULT 1,
          `date` date NOT NULL,
          `time` time NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: reservations
    mysqli_query($dbc, "
        CREATE TABLE IF NOT EXISTS `reservations` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` varchar(255) NOT NULL,
          `mail` varchar(255) NOT NULL,
          `phone` varchar(50) NOT NULL,
          `visitors` int(11) NOT NULL DEFAULT 1,
          `date` date NOT NULL,
          `time` time NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: about
    mysqli_query($dbc, "
        CREATE TABLE IF NOT EXISTS `about` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `about_text` text NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed Admin Account (Username: admin, Password: 1234)
    $hashed_pwd = password_hash('1234', PASSWORD_DEFAULT);
    mysqli_query($dbc, "INSERT INTO `admin` (`id`, `name`, `username`, `password`) VALUES
        (1, 'Administrator', 'admin', '{$hashed_pwd}')
        ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");

    // Seed About Us Content
    $about_intro = "Herzlich Willkommen im Restaurant El Paso! Genießen Sie authentische Steaks, mexikanische Spezialitäten, frische Burger und erlesene Weine in einer stimmungsvollen und herzlichen Atmosphäre.";
    $about_escaped = mysqli_real_escape_string($dbc, $about_intro);
    mysqli_query($dbc, "INSERT INTO `about` (`id`, `about_text`) VALUES
        (1, '{$about_escaped}')
        ON DUPLICATE KEY UPDATE `about_text`=VALUES(`about_text`);");

    // Seed Sample Confirmed & Pending Reservations
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    mysqli_query($dbc, "INSERT INTO `reservations` (`id`, `name`, `mail`, `phone`, `visitors`, `date`, `time`) VALUES
        (1, 'Schmidt Familie', 'schmidt@web.de', '+49 171 1234567', 4, '{$today}', '19:00:00'),
        (2, 'Weber Thomas', 't.weber@gmx.de', '+49 160 9876543', 2, '{$tomorrow}', '20:00:00')
        ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");

    mysqli_query($dbc, "INSERT INTO `reservations_pending` (`id`, `name`, `mail`, `phone`, `visitors`, `date`, `time`) VALUES
        (1, 'Müller Klaus', 'klaus.m@gmail.com', '+49 151 5554433', 3, '{$today}', '18:30:00')
        ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");
}
?>
