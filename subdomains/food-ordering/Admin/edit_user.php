<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $firstName = $_POST['firstName'];
  $lastName = $_POST['lastName'];
  $contact = $_POST['contact'];
  $password = isset($_POST['password']) ? $_POST['password'] : '';

  // Update the user data
  if (!empty($password)) {
    $sql = "UPDATE users SET firstName='$firstName', lastName='$lastName', contact='$contact', password='$password' WHERE email='$email'";
  } else {
    $sql = "UPDATE users SET firstName='$firstName', lastName='$lastName', contact='$contact' WHERE email='$email'";
  }

  if ($conn->query($sql) === TRUE) {
    echo "User updated successfully.";
  } else {
    echo "Error updating user: " . $conn->error;
  }

  $conn->close();
  header("Location: users.php");
  exit();
}
?>
