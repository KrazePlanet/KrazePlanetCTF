<?php

include 'db_connection.php';

// Get the logged-in admin's email from the session
$admin_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

if (empty($admin_email)) {
  die("Admin email not found in session.");
}


// Function to retrieve admin information
function getAdminInfo($email)
{
  global $conn;
  $stmt = $conn->prepare("SELECT firstName, lastName, email, profile_image FROM staff WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->bind_result($firstName, $lastName, $email,  $profile_image);
  $stmt->fetch();
  $stmt->close();
  return [
    'firstName' => $firstName ?: '',
    'lastName' => $lastName ?: '',
    'email' => $email ?: '',
    'profile_image' => $profile_image ?: 'default.jpg'
  ];
}

$admin_info = getAdminInfo($admin_email);
?>