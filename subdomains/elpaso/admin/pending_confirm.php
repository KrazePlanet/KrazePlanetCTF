<?php
    include 'connect.php';
    $id = $_GET['id'];

    if ($dbc) {
        $query = "SELECT * FROM reservations_pending WHERE id=$id";
        $result = mysqli_query($dbc, $query);
        while($row = mysqli_fetch_array($result)) {
            $famName = $row['name'];
            $famMail = $row['mail'];
            $famPhone = $row['phone'];
            $famVisitors = $row['visitors'];
            $famDate = $row['date'];
            $famTime = $row['time'];
        }

        $query = "INSERT INTO reservations (name, mail, phone, visitors, date, time) VALUES ('$famName', '$famMail', '$famPhone', '$famVisitors', '$famDate', '$famTime')";
        $result = mysqli_query($dbc,$query) or die('Error querying database.');

        $query = "DELETE FROM reservations_pending WHERE id=$id";
        $result = mysqli_query($dbc,$query) or die('Error querying database.');
        header('Location:reservation_check.php');
    }
?>