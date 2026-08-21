<?php
include 'login1.php'; // Database connection

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Delete course from database
    $query = "DELETE FROM course WHERE code='$code'";
    mysqli_query($con, $query);
    
    // Redirect back to manage courses page
    header("Location: manage_course.php");
    exit();
}
?>
