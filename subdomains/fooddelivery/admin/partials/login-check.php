<?php 
    if (!isset($_SESSION['user']))
    {
        $_SESSION['no-login']="<div class='error'>Please Login to access Admin.</div>";
        header('location:'.SITEURL.'admin/login.php');
    }
?>