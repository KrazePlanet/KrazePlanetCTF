<?php
$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$dbuser = "root";
$dbpass = "";
$dbase = "enigma";

// 1. Initial Connection to Ensure Database Exists
$init_conn = @mysqli_connect($host, $dbuser, $dbpass);
if ($init_conn) {
    @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `" . $dbase . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_conn);
}

// 2. Connect to enigma database
$dbc = @mysqli_connect($host, $dbuser, $dbpass, $dbase);

if (!$dbc) {
    die("Database Connection failed: " . mysqli_connect_error());
}

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @mysqli_query($dbc, "SHOW TABLES LIKE 'admin'");
if (!$chk || mysqli_num_rows($chk) == 0) {

    // Create Admin Table
    @mysqli_query($dbc, "
        CREATE TABLE IF NOT EXISTS `admin` (
          `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(255) NOT NULL,
          `pass` VARCHAR(255) NOT NULL,
          `email` VARCHAR(255) NOT NULL UNIQUE,
          `isaccess` INT(10) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create Events Table
    @mysqli_query($dbc, "
        CREATE TABLE IF NOT EXISTS `events` (
          `eid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(255) NOT NULL,
          `description` VARCHAR(1000) NOT NULL,
          `type` VARCHAR(255) NOT NULL,
          `time` VARCHAR(255) DEFAULT NULL,
          `date` VARCHAR(255) DEFAULT NULL,
          `image` VARCHAR(1000) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create Users Table
    @mysqli_query($dbc, "
        CREATE TABLE IF NOT EXISTS `users` (
          `uid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(255) NOT NULL,
          `email` VARCHAR(255) NOT NULL UNIQUE,
          `phone` VARCHAR(255) NOT NULL,
          `year` VARCHAR(255) NOT NULL,
          `password` VARCHAR(255) NOT NULL,
          `dept` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create Participants Table
    @mysqli_query($dbc, "
        CREATE TABLE IF NOT EXISTS `participants` (
          `pid` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `eid` INT(11) NOT NULL,
          `uid` INT(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed Admins (admin@enigma.com / admin, vijai@mit.edu / 1234 & admin)
    @mysqli_query($dbc, "INSERT INTO `admin` (`id`, `name`, `pass`, `email`, `isaccess`) VALUES
        (1, 'Administrator', 'admin', 'admin@enigma.com', 1),
        (2, 'Vijai Suria', '1234', 'vijai@mit.edu', 1)
        ON DUPLICATE KEY UPDATE `pass`=VALUES(`pass`);");

    // Seed Sample Events
    @mysqli_query($dbc, "INSERT INTO `events` (`eid`, `name`, `description`, `type`, `time`, `date`, `image`) VALUES
        (1, 'National Hackathon 2026', '24-hour continuous coding marathon solving real-world challenges.', 'Technical', '09:00 AM', '2026-09-15', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800'),
        (2, 'AI & Robotics Symposium', 'Keynotes, paper presentations, and autonomous robot demonstrations.', 'Symposium', '10:30 AM', '2026-09-18', 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=800'),
        (3, 'Web3 & Security Workshop', 'Hands-on smart contract auditing, web vulnerabilities and exploit mitigation.', 'Workshop', '02:00 PM', '2026-09-22', 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=800'),
        (4, 'UI/UX Design Sprint', 'Rapid prototyping, usability testing and visual design competition.', 'Design', '11:00 AM', '2026-09-25', 'https://images.unsplash.com/photo-1581291518857-4e27b48ff24e?w=800');");

    // Seed Sample Users
    @mysqli_query($dbc, "INSERT INTO `users` (`uid`, `name`, `email`, `phone`, `year`, `password`, `dept`) VALUES
        (1, 'Alex Vance', 'alex@mit.edu', '+1 555-019-2831', '3rd Year', 'password123', 'Computer Science'),
        (2, 'Sarah Connor', 'sarah@mit.edu', '+1 555-829-1049', '4th Year', 'password123', 'Cyber Security'),
        (3, 'Marcus Brody', 'marcus@mit.edu', '+1 555-391-7720', '2nd Year', 'password123', 'Information Technology');");

    // Seed Sample Participants
    @mysqli_query($dbc, "INSERT INTO `participants` (`pid`, `eid`, `uid`) VALUES
        (1, 1, 1),
        (2, 1, 2),
        (3, 2, 1),
        (4, 3, 2),
        (5, 4, 3);");
}
?>
