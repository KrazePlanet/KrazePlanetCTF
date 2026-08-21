<?php
include 'login1.php'; // Database connection

if (isset($_GET['f_id'])) {
    $f_id = $_GET['f_id'];
    
    // Delete faculty from database
    $query = "DELETE FROM faculty WHERE f_id='$f_id'";
    mysqli_query($con, $query);
    
    // Redirect back to manage faculty page
    header("Location: manage_faculty.php");
    exit();
}
?>
