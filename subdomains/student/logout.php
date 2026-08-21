<?php
require_once 'config.php';

if (isAdminLoggedIn()) {
    logActivity($conn, "Admin logged out: {$_SESSION['admin_name']}");
}

session_destroy();
header('Location: ' . APP_URL . '/login.php?logout=1');
exit();
?>