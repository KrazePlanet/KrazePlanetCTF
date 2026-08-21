<?php
// Include your database connection code here
include "config.php";
session_start();
// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["whale_enterprises_loggedin"]) || $_SESSION["whale_enterprises_loggedin"] !== true) {
    header("location: login.php");
    exit;
}
// Fetch data from MySQL and format it for the download file
$sql = "SELECT * FROM software_model";
$result = mysqli_query($link, $sql);

if ($result) {
    // Create a temporary file to store the data
    $tempFile = tempnam(sys_get_temp_dir(), "data_");
    $fileHandle = fopen($tempFile, 'w');

    // Write column headers to the file
    $headers = array("Drawing Number", "Revision Number", "Description", "Sector", "Inserted User", "Last Revision"); // Replace with your actual column names
    fputcsv($fileHandle, $headers);

    // Write data rows to the file
    while ($row = mysqli_fetch_assoc($result)) {
        $rowData = array(
            $row['drawing_number'], // Replace with your actual column names
            $row['revision_number'],
            $row['description'],
            $row['sector'],
            $row['inserted_user'],
            $row['last_rev_date']

        );
        fputcsv($fileHandle, $rowData);
    }

    fclose($fileHandle);

    // Set the appropriate headers for file download
    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename="data.csv"'); // Replace with your desired file name

    // Output the file contents
    readfile($tempFile);

    // Delete the temporary file
    unlink($tempFile);
} else {
    // Handle MySQL query error
    echo "Error: " . mysqli_error($link);
}
