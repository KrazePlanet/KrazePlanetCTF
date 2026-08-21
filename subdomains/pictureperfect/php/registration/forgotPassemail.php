<?php
session_start();
$email=$_SESSION['email'];
$token = bin2hex(random_bytes(15));
$_SESSION['token'] = $token;
$subject = "Password Reset";
$body = "click on the link below to reset your password
http://localhost/PICTURE%20PERFECT%20PROJECT/php/registration/forgotPassword.php?token='$token'";
$my_email = "From: Pictureperfectcor@gmail.com";

if (mail($email, $subject, $body, $my_email)) {
    $_SESSION['pass-message'] = "check your email ($email) to reset your password";
    header('location:login.php');
} else {
    echo "Email sending failed...";
}
?>