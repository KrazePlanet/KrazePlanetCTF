<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // Get POST data
  $email = $_POST['email'];
  $name = $_POST['name'];
  $contact = $_POST['contact'];
  $noOfGuests = $_POST['noOfGuests'];
  $reservedTime = $_POST['reservedTime']; // Input format is 'HH:MM'
  $reservedDate = $_POST['reservedDate']; // Input format is 'YYYY-MM-DD'
  $status = $_POST['status'];
  $reservation_id = $_POST['reservation_id']; // Assuming reservation_id is sent via POST

  // Prepare and bind
  $stmt = $conn->prepare("UPDATE reservations SET email=?, name=?, contact=?, noOfGuests=?, reservedTime=?, reservedDate=?, status=? WHERE reservation_id=?");
  $stmt->bind_param("sssssssi", $email, $name, $contact, $noOfGuests, $reservedTime, $reservedDate, $status, $reservation_id);

  // Execute and check
  if ($stmt->execute()) {
    echo "Reservation updated successfully.";
  } else {
    echo "Error Updating Reservation: " . $stmt->error;
  }

  // Close connection
  $stmt->close();
  $conn->close();

  // Redirect
  header("Location: reservations.php");
  exit();
}
?>
