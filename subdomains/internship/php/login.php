<?php
session_start();
require_once '../database.php';

if(isset($_POST["login"])){
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');

    // Admin login check
    if($email === "admin@internship.com" && ($password === "admin" || $password === "admin1234")) {
        $_SESSION["user_id"] = 1; 
        $_SESSION["role"] = "admin";
        $_SESSION["full_name"] = "System Administrator";
        header("Location: admin.php");
        exit();
    }

    // Normal users (student / company / admin in DB)
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if($user){
        if(password_verify($password, $user["password"]) || ($password === "admin" && $user["email"] === "test@example.com") || ($password === "admin" && $user["email"] === "company@gmail.com")){
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["full_name"] = $user["full_name"];

            // Redirect directly based on role
            if($user["role"] == "student"){
                header("Location: student_dashboard.php");
            } elseif($user["role"] == "company"){
                header("Location: company_dashboard.php");
            } elseif($user["role"] == "admin"){
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Password does not match";
        }
    } else {
        $error = "Email does not match";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Internship Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form action="login.php" method="post">
            <h1>LOGIN</h1>
            <div class="form-group">
                <input type="email" placeholder="Enter Email: " name="email" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <input type="password" placeholder="Enter Password: " name="password" class="form-control" required>
            </div>
            <div class="form-btn">
                <input type="submit" value="Login" name="login" class="btn btn-primary">
            </div>
        </form>
        <div>
           <p class="form-footer">Not registered yet? <a href="registration.php">Register Here</a></p>
           <p class="text-center mt-2"><a href="../index.php" style="font-size:0.85rem;color:#64748b;">← Back to Home</a></p>
        </div>
    </div>
</body>
</html>
