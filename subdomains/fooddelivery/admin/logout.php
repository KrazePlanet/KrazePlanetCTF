<?php 
include('../config/constants.php');
    session_destroy();
    header('location:'.SITEURL.'admin/login.php');
    
?>