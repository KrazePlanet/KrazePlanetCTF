<?php
require_once dirname(dirname(__DIR__)) . '/connect.php';

session_start();

if (!(isset($_SESSION['Aname']))) {
	echo "Unauthorized Access";
	return;
}

$id = $_GET['id'];
$DelSql = "DELETE FROM `events` WHERE eid=$id";
$res = mysqli_query($dbc, $DelSql);
if ($res) {
	header('location: ../../events.php');
} else {
	echo "Failed to delete";
}
?>