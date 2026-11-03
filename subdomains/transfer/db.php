<?php
mysqli_report(MYSQLI_REPORT_OFF);

$_host = getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? '127.0.0.1' : '127.0.0.1');
$_user = getenv('DB_USER') ?: 'root';
$_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$_name = 'KrazePlanet_DB';

$conn = @mysqli_connect($_host, $_user, $_pass);
if ($conn) {
    @mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    @mysqli_select_db($conn, $_name);

    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab57914_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        nickname TEXT,
        password VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab57914_invites (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_email VARCHAR(255) DEFAULT '',
        to_email VARCHAR(255) DEFAULT '',
        nickname TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    // Add password column to existing tables (safe to run each time)
    @mysqli_query($conn, "ALTER TABLE lab57914_users ADD COLUMN IF NOT EXISTS password VARCHAR(255) DEFAULT NULL");
}
