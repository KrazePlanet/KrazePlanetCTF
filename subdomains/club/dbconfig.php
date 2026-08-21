<?php
global $con;

$hostname = 'localhost';
$user     = 'root';
$password = '';
$dbname   = 'db';

// 1. Initial Connection to Ensure Database Exists
$init = @new mysqli($hostname, $user, $password);
if ($init && !$init->connect_error) {
    @$init->query("CREATE DATABASE IF NOT EXISTS `" . $dbname . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @$init->close();
}

// 2. Connect to database
$con = @new mysqli($hostname, $user, $password, $dbname);

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    die();
}

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @$con->query("SHOW TABLES LIKE 'userinfo'");
if (!$chk || $chk->num_rows == 0) {
    
    // Create attendance table
    $con->query("
        CREATE TABLE IF NOT EXISTS `attendance` (
          `attendance_id` INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `session_id` INT(5) NOT NULL,
          `id_array` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create blog_posts table
    $con->query("
        CREATE TABLE IF NOT EXISTS `blog_posts` (
          `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `postTitle` VARCHAR(200) NOT NULL,
          `description` TEXT NOT NULL,
          `content` TEXT NOT NULL,
          `post_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `auther` VARCHAR(25) NOT NULL,
          `catinfo` VARCHAR(70) DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create notice table
    $con->query("
        CREATE TABLE IF NOT EXISTS `notice` (
          `notice_id` INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `title` VARCHAR(150) NOT NULL,
          `description` TEXT NOT NULL,
          `date` VARCHAR(25) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create sessions table
    $con->query("
        CREATE TABLE IF NOT EXISTS `sessions` (
          `session_id` INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `session_name` VARCHAR(150) NOT NULL,
          `session_details` VARCHAR(250) NOT NULL,
          `session_date` VARCHAR(25) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create userinfo table
    $con->query("
        CREATE TABLE IF NOT EXISTS `userinfo` (
          `id` INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(50) NOT NULL,
          `email` VARCHAR(70) DEFAULT NULL UNIQUE,
          `dob` VARCHAR(25) NOT NULL,
          `username` VARCHAR(25) NOT NULL UNIQUE,
          `password` TEXT NOT NULL,
          `role` VARCHAR(20) DEFAULT NULL,
          `last_login` DATETIME NOT NULL,
          `currunt_login` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `otp` VARCHAR(10) NOT NULL,
          `pic` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed Default Users (admin / 12345 & admin / admin, member / member)
    $now = date('Y-m-d H:i:s');
    $con->query("INSERT INTO `userinfo` (`id`, `name`, `email`, `dob`, `username`, `password`, `role`, `last_login`, `currunt_login`, `otp`, `pic`) VALUES
        (1, 'Admin', 'admin@somepanel.com', '1995-01-01', 'admin', '12345', 'President', '{$now}', '{$now}', '389531', 'imgs/17241-200.png'),
        (2, 'Member', 'member@somepanel.com', '1998-05-12', 'member', 'member', 'Member', '{$now}', '{$now}', '', 'imgs/user.png');");

    // Seed Sample Notice
    $con->query("INSERT INTO `notice` (`notice_id`, `title`, `description`, `date`) VALUES
        (1, 'Welcome to Club Manager', 'Welcome all new members to the annual club session and development meetup.', '" . date('d-m-Y H:i') . "');");

    // Seed Sample Session
    $con->query("INSERT INTO `sessions` (`session_id`, `session_name`, `session_details`, `session_date`) VALUES
        (1, 'Agile Development Workshop', 'Hands-on sprint planning, code review and architecture design meetup.', '" . date('d-m-Y H:i') . "');");

    // Seed Sample Blog Post
    $con->query("INSERT INTO `blog_posts` (`id`, `postTitle`, `description`, `content`, `post_date`, `auther`, `catinfo`) VALUES
        (1, 'Hello Club Members!', 'Welcome to Club Manager blogging platform.', '<h3>Welcome to Club Manager!</h3><p>Manage members, attendance, events and announcements seamlessly.</p>', '{$now}', 'admin', 'Technology');");
    
    // Seed Sample Attendance
    $con->query("INSERT INTO `attendance` (`attendance_id`, `session_id`, `id_array`) VALUES
        (1, 1, 'a:1:{i:0;s:1:\"1\";}');");
}
?>
