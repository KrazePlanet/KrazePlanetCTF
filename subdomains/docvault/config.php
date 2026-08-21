<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'whale_enterprises';

// 1. Initial Connection to Ensure Database Exists
$init_conn = @mysqli_connect($host, $user, $pass);
if ($init_conn) {
    @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `" . $dbname . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_conn);
}

// 2. Connect to whale_enterprises database
$link = @mysqli_connect($host, $user, $pass, $dbname);
if (!$link) {
    die('Could not connect: ' . mysqli_connect_error());
}

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @mysqli_query($link, "SHOW TABLES LIKE 'users'");
if (!$chk || mysqli_num_rows($chk) == 0) {

    // Create sectors table
    @mysqli_query($link, "
        CREATE TABLE IF NOT EXISTS `sectors` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(100) DEFAULT NULL,
          `sector` VARCHAR(100) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create software_model table
    @mysqli_query($link, "
        CREATE TABLE IF NOT EXISTS `software_model` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `drawing_number` VARCHAR(45) NOT NULL,
          `revision_number` VARCHAR(45) NOT NULL,
          `description` LONGTEXT NOT NULL,
          `file` VARBINARY(255) NOT NULL,
          `sector` VARCHAR(45) DEFAULT NULL,
          `last_rev_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create users table
    @mysqli_query($link, "
        CREATE TABLE IF NOT EXISTS `users` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(100) NOT NULL,
          `password` VARCHAR(255) NOT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `name` VARCHAR(255) DEFAULT NULL,
          `phone_number` VARCHAR(15) DEFAULT NULL,
          `designation` VARCHAR(255) DEFAULT 'User',
          `code` VARCHAR(10) DEFAULT '0',
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed SuperAdmin & Demo Accounts (ternos / password & admin / admin)
    $pwd_hash = password_hash('password', PASSWORD_DEFAULT);
    $admin_pwd = password_hash('admin', PASSWORD_DEFAULT);
    @mysqli_query($link, "INSERT INTO `users` (`id`, `username`, `password`, `name`, `phone_number`, `designation`, `code`) VALUES
        (1, 'ternos', '{$pwd_hash}', 'Whale Enterprises Admin', '9566555628', 'SuperAdmin', '0'),
        (2, 'admin', '{$admin_pwd}', 'System Administrator', '9876543210', 'SuperAdmin', '0')
        ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");

    // Seed Sectors
    @mysqli_query($link, "INSERT INTO `sectors` (`id`, `username`, `sector`) VALUES
        (1, 'ternos', 'Parts'),
        (2, 'ternos', 'Machine Shop'),
        (3, 'ternos', 'New Product Development'),
        (4, 'ternos', 'Fabrication');");

    // Seed Sample Drawing Models
    @mysqli_query($link, "INSERT INTO `software_model` (`id`, `drawing_number`, `revision_number`, `description`, `file`, `sector`) VALUES
        (1, 'DWG-2026-001', 'REV-A', 'Machined flange assembly schematic drawing', '641533COE.pdf', 'Machine Shop'),
        (2, 'DWG-2026-002', 'REV-B', 'Heavy duty fabrication structural bracket', '976158COE.pdf', 'Fabrication'),
        (3, 'DWG-2026-003', 'REV-A', 'Hydraulic valve precision component model', '368168Q013351640.pdf', 'Parts'),
        (4, 'DWG-2026-004', 'REV-C', 'Next-gen turbine prototype CAD specification', '778496bio_hack_abstract.pdf', 'New Product Development');");
}
?>
