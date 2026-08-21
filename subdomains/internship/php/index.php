<?php
session_start();
if (isset($_SESSION["role"])) {
    if ($_SESSION["role"] == "admin") {
        header("Location: admin.php");
    } elseif ($_SESSION["role"] == "student") {
        header("Location: student_dashboard.php");
    } elseif ($_SESSION["role"] == "company") {
        header("Location: company_dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internship Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/index.css">

</head>
<body class="bg-light">
    <div class="container text-center mt-5">
        <h1 class="mb-4">Welcome to Internship Management System</h1>
        <a href="login.php" class="btn btn-primary btn-lg">Login</a>
        <a href="registration.php" class="btn btn-success btn-lg">Register</a>
    </div>
</body>
</html>
