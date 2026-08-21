<?php
require_once 'config.php';

if (isAdminLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('login.php');
}
?>