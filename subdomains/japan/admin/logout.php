<?php

session_start();

session_unset();
session_destroy();

header('Location: /japan/admin/login.php');

exit;
