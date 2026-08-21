<?php

$serverName = "localhost";
$user="root";
$password = "";
$db = "picture perfect";

$conn = mysqli_connect($serverName,$user,$password,$db);
if (!$conn) {
    echo "Could not connect to server";
}
?>
