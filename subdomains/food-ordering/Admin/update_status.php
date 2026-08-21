<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Include database connection
  include 'db_connection.php';

  $reservation_id = $_POST['reservation_id'];
  $status = $_POST['status'];

  // Prepare and execute the update query
  $stmt = $conn->prepare("UPDATE reservations SET status = ? WHERE reservation_id = ?");
  $stmt->bind_param("si", $status, $reservation_id);

  if ($stmt->execute()) {
    echo "Status updated successfully";
  } else {
    echo "Error updating status: " . $conn->error;
  }

  $stmt->close();
  $conn->close();
}




?>

