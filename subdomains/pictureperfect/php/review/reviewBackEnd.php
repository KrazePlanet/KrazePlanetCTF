<?php
session_start();
require('../registration/dbcon.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $points = $_POST['points'];
  $reviewTitle = $_POST['reviewTitle'];
  $reviewSummary = $_POST['reviewSummary'];
  $role=$_SESSION['role'];
  $rname = $_SESSION['uname'];
  $email = $_SESSION['email'];
  $sql = "INSERT INTO review (rname, remail, rtitle, rsummary, points, role) VALUES ('$rname',' $email','$reviewTitle','$reviewSummary','$points','$role')";
  $result = mysqli_query($conn, $sql);
  if (!$result) {
    $error = mysqli_error($conn);
    $response = array('success' => false, 'error' => $error);
    header('Content-type: application/json');
    echo json_encode($response);
  } else {
    $response = array('success' => true);
    header('Content-type: application/json');
    echo json_encode($response);
  }
}
?>