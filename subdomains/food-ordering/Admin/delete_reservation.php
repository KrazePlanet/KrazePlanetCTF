<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Include database connection
  include 'db_connection.php';

  $reservation_id = $_POST['reservation_id'];

  // Prepare and execute the deletion query
  $stmt = $conn->prepare("DELETE FROM reservations WHERE reservation_id = ?");
  $stmt->bind_param("i", $reservation_id);

  if ($stmt->execute()) {
    echo "Reservation deleted successfully";
  } else {
    echo "Error deleting reservation: " . $conn->error;
  }

  $stmt->close();
  $conn->close();
}
?>
