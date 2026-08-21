<?php
    include 'connect.php';
    $id = $_GET['id'];
    if ($dbc) {
        $query = "DELETE FROM reservations WHERE id=$id";
        $result = mysqli_query($dbc, $query);
        if($result) {
            echo "<script LANGUAGE='JavaScript'>
            window.alert('Succesfully deleted!');
            window.location.href='reservations.php';
            </script>";
        }
    }
?>