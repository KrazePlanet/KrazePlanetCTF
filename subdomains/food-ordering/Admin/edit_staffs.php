<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = $_POST['email'];
  $firstName = $_POST['firstName'];
  $lastName = $_POST['lastName'];
  $contact = $_POST['contact'];
  $role = $_POST['role'];
  $password = isset($_POST['password']) ? $_POST['password'] : '';

  // Update the user data
  if (!empty($password)) {
    $sql = "UPDATE staff SET firstName='$firstName', lastName='$lastName', contact='$contact', role='$role', password='$password' WHERE email='$email'";
  } else {
    $sql = "UPDATE users SET firstName='$firstName', lastName='$lastName', contact='$contact', role='$role' WHERE email='$email'";
  }

  if ($conn->query($sql) === TRUE) {
    echo "Staff updated successfully.";
  } else {
    echo "Error updating user: " . $conn->error;
  }

  $conn->close();
  header("Location: staffs.php");
  exit();
}
?>
