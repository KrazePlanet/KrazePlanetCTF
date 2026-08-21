<?php
// Disable default mysqli exceptions in PHP 8+ to prevent white screen of death on connection errors
mysqli_report(MYSQLI_REPORT_OFF);

// MySQL database details
$DATABASE_HOST = 'krazeplanet';
$DATABASE_NAME = 'KrazePlanet';
$DATABASE_USER = 'root';
$DATABASE_PASS = '';

// Step 1: Attempt direct connection with Database Name (standard for cPanel & production)
$con = @mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS, $DATABASE_NAME);

// Step 2: Fallback for local development if database does not exist yet
if (!$con) {
    // Try connecting to MySQL server without database
    $con = @mysqli_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASS);
    if (!$con) {
        exit('<div style="font-family:sans-serif;padding:20px;background:#fff0f0;color:#c0392b;border:1px solid #f5c6c6;border-radius:6px;max-width:600px;margin:40px auto;text-align:center;">
            <h3>Database Connection Failed</h3>
            <p>' . htmlspecialchars(mysqli_connect_error()) . '</p>
            <p><small>Check $DATABASE_USER and $DATABASE_PASS in db.php</small></p>
        </div>');
    }

    // Try creating database (works on local XAMPP, usually restricted on cPanel)
    @$con->query("CREATE DATABASE IF NOT EXISTS `" . $con->real_escape_string($DATABASE_NAME) . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Select database
    if (!@$con->select_db($DATABASE_NAME)) {
        exit('<div style="font-family:sans-serif;padding:20px;background:#fff0f0;color:#c0392b;border:1px solid #f5c6c6;border-radius:6px;max-width:600px;margin:40px auto;text-align:center;">
            <h3>Database Selection Failed</h3>
            <p>Database <strong>' . htmlspecialchars($DATABASE_NAME) . '</strong> does not exist or user access is denied.</p>
            <p><small>Please create database <strong>' . htmlspecialchars($DATABASE_NAME) . '</strong> in cPanel MySQL Databases and assign your database user to it.</small></p>
        </div>');
    }
}

// Step 3: Create the accounts table if it does not exist
$con->query("CREATE TABLE IF NOT EXISTS `accounts` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `email` varchar(100) NOT NULL,
    `registered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Step 4: Create the pending_registrations table for OTP email verification
$con->query("CREATE TABLE IF NOT EXISTS `pending_registrations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `email` varchar(100) NOT NULL,
    `otp` varchar(6) NOT NULL,
    `expires_at` datetime NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
?>