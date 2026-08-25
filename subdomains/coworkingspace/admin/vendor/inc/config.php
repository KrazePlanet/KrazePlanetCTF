<?php
$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$dbuser = 'root';
$dbpass = '';
$db = 'cowork_db';

// Ensure database exists
$init_conn = @new mysqli($host, $dbuser, $dbpass);
if ($init_conn && !$init_conn->connect_error) {
    @$init_conn->query("CREATE DATABASE IF NOT EXISTS `{$db}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @$init_conn->close();
}

$mysqli = new mysqli($host, $dbuser, $dbpass, $db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Trigger auto-provisioning via connect.php if not yet run
require_once dirname(__DIR__, 3) . '/components/connect.php';
?>
