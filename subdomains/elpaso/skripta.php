<?php
  include 'admin/connect.php';

  if ($dbc && isset($_POST['submit'])) {
    $name = $_POST['visitor_name'];
    $mail = $_POST['visitor_email'];
    $phone = $_POST['visitor_phone'];
    $visitors = $_POST['number_people'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    
    $query = "INSERT INTO reservations_pending (name, mail, phone, visitors, date, time) VALUES ('$name', '$mail', '$phone', '$visitors', '$date', '$time')";

    $result = mysqli_query($dbc,$query) or die('Error querying database.');

    header('Location:reservierung.php');
  }

  mysqli_close($dbc);
?>
