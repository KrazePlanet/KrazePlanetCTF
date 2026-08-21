<?php
session_start();
unset($_SESSION['foodie_admin']);
session_destroy();
header("Location: index.php");
exit();
?>
