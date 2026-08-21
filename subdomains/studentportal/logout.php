<?php
session_start();
session_destroy();
header('Location: category.html');
exit();
?>