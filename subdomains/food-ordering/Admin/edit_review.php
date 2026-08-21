<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Get POST data
  $response = $_POST['response'];
  $order_id = $_POST['order_id'];
  

  // Prepare and bind
  $stmt = $conn->prepare("UPDATE reviews SET response=? WHERE order_id=?");
  $stmt->bind_param("si", $response, $order_id);

  // Execute and check
  if ($stmt->execute()) {
    echo "Review updated successfully.";
  } else {
    echo "Error Updating Reservation: " . $stmt->error;
  }

  // Close connection
  $stmt->close();
  $conn->close();

  // Redirect
  header("Location: reviews.php");
  exit();
}
?>
