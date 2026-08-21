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

if (isset($_POST["loginUser"]) && isset($_POST['email']) && isset($_POST['pass'])) {
    $email = trim($_POST['email']);
    $pass = $_POST['pass'];

    $auth = new Auth();

    // Check for invalid characters in password
    if (!checkPass($pass)) {
        header("HTTP/1.0 406 Not Acceptable Password.");
        exit();
    }

    if (!$auth->checkAccountStatus($email)) {
        header("HTTP/1.0 403 Account Deactivated");
        exit();
    }

    // Password Encryption
    $pass_hash = sha1($pass);
    $res = $auth->loginUser($email, $pass_hash);

    if ($res === "404") {
        header("HTTP/1.0 404 User Not Found");
        exit();
    } elseif ($res === "200" || $res === "201") {
        echo json_encode(["status" => $res]);
        exit();
    } else {
        header("HTTP/1.0 500 Internal Server Error");
        exit();
    }
}
?>
