<?php

$serverName = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$user="root";
$password = "";
$db = "picture perfect";

$conn = mysqli_connect($serverName,$user,$password,$db);
if (!$conn) {
    echo "Could not connect to server";
}
?>
