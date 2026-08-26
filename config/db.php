<?php
// config/db.php - Optimized, High-Performance Database Connection

// Include existing configuration if present
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

try {
    // Primary direct connection (TCP 127.0.0.1 or configured host)
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Fallback: try localhost socket without delaying
    try {
        $altHost = ($db_host === '127.0.0.1') ? 'localhost' : '127.0.0.1';
        $pdo = new PDO("mysql:host={$altHost};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, $options);
    } catch (PDOException $e2) {
        $db_error = $e2->getMessage();
        error_log("KrazePlanet DB Connection Error: " . $db_error);
    }
}
