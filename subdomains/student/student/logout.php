<?php
require_once '../config.php';

if (isStudentLoggedIn()) {
    logActivity($conn, "Student logged out: {$_SESSION['student_name']}");
}

session_destroy();
header('Location: ' . APP_URL . '/student/login.php?logout=1');
exit();
?>