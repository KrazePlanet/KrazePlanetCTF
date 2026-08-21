<?php
if (!isset($_SESSION)) {
    session_start();
}

include_once "../app/_dbConnection.php";

function checkPass($pass)
{
    $invalid = array("=", "*", "-", "#", "$", "'");
    for ($x = 0; $x < strlen($pass); $x++) {
        for ($i = 0; $i < sizeof($invalid); $i++) {
            if ($pass[$x] == $invalid[$i]) {
                return false;
            }
        }
    }
    return true;
}

if (isset($_POST['newUser']) && isset($_POST['username']) && isset($_POST['email']) && isset($_POST['pass'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $pass = $_POST['pass'];

    $auth = new Auth();

    // Check if username exists
    if (!$auth->checkUserName($username)) {
        header("HTTP/1.0 406 Username Exists");
        exit();
    }

    // Check if Email exists
    if (!$auth->checkEmail($email)) {
        header("HTTP/1.0 406 Email Exists");
        exit();
    }

    // Check for invalid characters in password
    if (!checkPass($pass)) {
        header("HTTP/1.0 406 Not Acceptable Password.");
        exit();
    }

    // Password Encryption
    $pass_hash = sha1($pass);

    if ($auth->createUser($username, $email, $pass_hash) == '200') {
        echo '200';
        exit();
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        exit();
    }
}
?>
