<?php
session_start();

if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$error = "";

if (isset($_POST["login"])) {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "All fields are required.";
    } else {
        require_once "database.php";

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_array($result, MYSQLI_ASSOC);

        if ($user) {
            if (password_verify($password, $user["password"])) {
                $_SESSION["user"] = "yes";
                header("Location: index.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "Email not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Portal | Student Login</title>

    <!-- Shared Login / Register CSS -->
    <link rel="stylesheet" href="./assets/css/login-Registration.css">
</head>

<body>

    <div class="container">

        

        <!-- PAGE TITLE -->
        <h1>Student Login</h1>

        <!-- ✅ ERROR BLOCK (TOP — SAME AS REGISTER PAGE) -->
        <?php if (!empty($error)): ?>
            <div class="form-errors">
                <div class="alert alert-danger">
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- LOGIN FORM -->
        <form action="login.php" method="post" novalidate>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your university email"
                    required
                    autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password">
            </div>

            <div class="form-btn">
                <button type="submit" name="login" class="btn">
                    Login
                </button>
            </div>

        </form>

        <p class="form-footer">
            Not registered yet?
            <a href="registration.php">Create an account</a>
        </p>

    </div>

</body>

</html>