<?php

session_start();

include('./dbcon.php');

if(isset($_GET['token'])){
    $token = $_GET['token'];

    $updateQuery = "UPDATE registration SET status='active' WHERE token='$token'";
    $sql=mysqli_query($conn,$updateQuery);

    if($sql){
        if($_SESSION['msg']){
            $_SESSION['msg'] = "Account verified sign in to continue";
            header('location:login.php');
        }else{
            $_SESSION['msg'] = "Login to Continue";
            header('location:login.php');
        }
    }else{
        $_SESSION['msg'] = "Try again";
        header('location:registration.php');
    }
}

?>