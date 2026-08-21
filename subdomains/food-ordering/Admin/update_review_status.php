<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Include database connection
  include 'db_connection.php';

  // Get the POST data
  $order_id = $_POST['order_id'];
  $status = $_POST['status'];

  // Log received data for debugging
  error_log("Received order_id: $order_id, status: $status");

  // Prepare and execute the update query
  $stmt = $conn->prepare("UPDATE reviews SET status = ? WHERE order_id = ?");
  if ($stmt) {
    $stmt->bind_param("si", $status, $order_id);

    if ($stmt->execute()) {
      echo "Status updated successfully";
    } else {
      echo "Error updating status: " . $conn->error;
    }

    $stmt->close();
  } else {
    echo "Prepare statement failed: " . $conn->error;
  }

  $conn->close();
}
?>
