<?php
// config/db.php - Resilient, High-Performance Database Connection

if (file_exists(__DIR__ . '/database.php')) {
    include_once __DIR__ . '/database.php';
}

$db_host = isset($db_host) && !empty($db_host) ? $db_host : (getenv("DB_HOST") ?: "127.0.0.1");
$db_user = isset($db_user) && !empty($db_user) ? $db_user : (getenv("DB_USER") ?: "root");
$db_pass = isset($db_pass) ? $db_pass : (getenv("DB_PASS") !== false ? getenv("DB_PASS") : "");
$db_name = isset($db_name) && !empty($db_name) ? $db_name : (getenv("DB_NAME") ?: "KrazePlanet");

$pdo = null;
$db_error = null;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 2,
];

// Connection targets: 1. TCP 127.0.0.1, 2. UNIX Socket /var/run/mysqld/mysqld.sock, 3. localhost
$targets = [
    "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname={$db_name};charset=utf8mb4",
    "mysql:host=localhost;dbname={$db_name};charset=utf8mb4"
];

foreach ($targets as $dsn) {
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        $db_error = null;
        break;
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}

// Quick 150ms retry if MariaDB was briefly restarting
if (!$pdo) {
    usleep(150000);
    foreach ($targets as $dsn) {
        try {
            $pdo = new PDO($dsn, $db_user, $db_pass, $options);
            $db_error = null;
            break;
        } catch (PDOException $e) {
            $db_error = $e->getMessage();
        }
    }
}
