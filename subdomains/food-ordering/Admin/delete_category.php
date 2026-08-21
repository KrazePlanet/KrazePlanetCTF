<?php
include('db_connection.php'); // Include your database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $catName = $_POST['catName'];

    // Sanitize the input to prevent SQL injection
    $catName = mysqli_real_escape_string($conn, $catName);

    // Delete the category from the database
    $sql = "DELETE FROM menucategory WHERE catName='$catName'";
    if (mysqli_query($conn, $sql)) {
        echo "Category deleted successfully.";
    } else {
        echo "Error deleting category: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>
