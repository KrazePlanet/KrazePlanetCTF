<?php
include 'login1.php'; // Database connection

if (isset($_GET['s_id'])) {
    $s_id = $_GET['s_id'];
    
    // Delete student from database
    $query = "DELETE FROM register WHERE s_id='$s_id'";
    mysqli_query($con, $query);
    
    // Redirect back to manage students page
    header("Location: manage_students.php");
    exit();
}
?>
