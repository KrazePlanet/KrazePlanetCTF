<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "php_employee_management";

// 1. Initial Connection to Ensure Database Exists
$init_conn = @new mysqli($servername, $username, $password);
if ($init_conn && !$init_conn->connect_error) {
    @$init_conn->query("CREATE DATABASE IF NOT EXISTS `{$database}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @$init_conn->close();
}

// 2. Connect to php_employee_management database
$connection = @new mysqli($servername, $username, $password, $database);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @$connection->query("SHOW TABLES LIKE 'employee'");
if (!$chk || $chk->num_rows == 0) {

    // Table: employee
    $connection->query("
        CREATE TABLE IF NOT EXISTS `employee` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` varchar(100) NOT NULL,
          `email` varchar(100) NOT NULL,
          `phone` varchar(20) NOT NULL,
          `address` varchar(255) NOT NULL,
          `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed Sample Employees
    $connection->query("INSERT INTO `employee` (`id`, `name`, `email`, `phone`, `address`, `created_at`) VALUES
        (1, 'John Doe', 'john.doe@corporate.com', '+1-555-0199', '742 Evergreen Terrace, Springfield', NOW()),
        (2, 'Sarah Jenkins', 'sarah.j@techcorp.io', '+1-555-0284', '100 Market Street, San Francisco, CA', NOW()),
        (3, 'Michael Scott', 'm.scott@dundermifflin.com', '+1-555-0371', '1725 Slough Avenue, Scranton, PA', NOW()),
        (4, 'Dwight Schrute', 'dwight.s@schrutefarms.com', '+1-555-0452', 'Schrute Farms, Honesdale, PA', NOW())
        ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");
}
?>
