<?php
require_once 'connection.php';

if (isset($_POST["submit"])) {
    $name = mysqli_real_escape_string($conn, trim($_POST["name"] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST["email"] ?? ''));
    $password = mysqli_real_escape_string($conn, trim($_POST["password"] ?? ''));

    $sql_query = "SELECT * FROM users WHERE fullname='$name' AND email='$email' AND userPassword='$password'";
    $result = mysqli_query($conn, $sql_query);
    $count = mysqli_num_rows($result);

    if ($count >= 1) {
        session_start();
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        header("location: ../index.php");
        exit();
    } else {
        echo "<script>alert('Invalid name, email or password.'); window.location.href='../login.html';</script>";
    }
    mysqli_close($conn);
} else {
    header("location: ../login.html");
    exit();
}
?>
