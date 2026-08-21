<?php
header('Content-Type: application/json');

include 'db_connection.php';

// Query to fetch review data
$sql = "SELECT review_date, rating, COUNT(*) as count FROM reviews GROUP BY review_date, rating ORDER BY review_date";
$result = $conn->query($sql);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$conn->close();

// Output data in JSON format
echo json_encode($data);
?>