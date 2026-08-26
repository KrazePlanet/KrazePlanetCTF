<?php
// Database configuration
$db_host = getenv("DB_HOST") ?: "127.0.0.1";
$db_user = getenv("DB_USER") ?: "root";
$db_pass = getenv("DB_PASS") !== false ? getenv("DB_PASS") : "";
$db_name = getenv("DB_NAME") ?: "KrazePlanet";
?>
