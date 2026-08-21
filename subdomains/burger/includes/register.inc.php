<?php
require_once 'connection.php';

if (isset($_POST["submit"])) {
    $name = mysqli_real_escape_string($conn, trim($_POST["name"] ?? ''));
    $email = mysqli_real_escape_string($conn, trim($_POST["email"] ?? ''));
    $password = mysqli_real_escape_string($conn, trim($_POST["password"] ?? ''));

    if (!empty($name) && !empty($email) && !empty($password)) {
        $sql = "INSERT INTO users (fullname, email, userPassword) VALUES ('$name', '$email', '$password')";
        
        if (mysqli_query($conn, $sql)) {
            session_start();
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email;
            header("location: ../index.php");
            exit();
        } else {
            echo "<script>alert('Registration failed. Email might already exist.'); window.location.href='../register.html';</script>";
        }
    } else {
        echo "<script>alert('All fields are required.'); window.location.href='../register.html';</script>";
    }
    mysqli_close($conn);
} else {
    header("location: ../register.html");
    exit();
}
?>
