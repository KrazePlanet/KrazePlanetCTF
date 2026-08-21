<?php
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Portal | Registration</title>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="./assets/css/login-Registration.css">
</head>

<body>

    <div class="container">
        <h1>Student Registration</h1>
        <?php
        if (isset($_POST["submit"])) {
            $fullname = $_POST["fullname"];
            $email = $_POST["email"];
            $password = $_POST["password"];
            $passwordrepeat = $_POST["repeat_password"];

            $passwordhash = password_hash($password, PASSWORD_DEFAULT);
            $errors = [];

            if (empty($fullname) || empty($email) || empty($password) || empty($passwordrepeat)) {
                $errors[] = "All fields are required.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please enter a valid email address.";
            }
            if (strlen($password) < 8) {
                $errors[] = "Password must be at least 8 characters long.";
            }
            if ($password !== $passwordrepeat) {
                $errors[] = "Passwords do not match.";
            }

            require_once "database.php";
            $sql = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "This email is already registered.";
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    echo "<div class='alert alert-danger'>$error</div>";
                }
            } else {
                $sql = "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)";
                $stmt = mysqli_stmt_init($conn);

                if (mysqli_stmt_prepare($stmt, $sql)) {
                    mysqli_stmt_bind_param($stmt, "sss", $fullname, $email, $passwordhash);
                    mysqli_stmt_execute($stmt);
                    echo "<div class='alert alert-success'>Registration successful. You can now log in.</div>";
                } else {
                    echo "<div class='alert alert-danger'>Something went wrong. Please try again.</div>";
                }
            }
        }
        ?>



        <form action="registration.php" method="post" novalidate>

            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input
                    type="text"
                    id="fullname"
                    name="fullname"
                    placeholder="Enter your full name"
                    required>
            </div>

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
                    placeholder="Minimum 8 characters"
                    required
                    autocomplete="new-password">
                <small class="hint">Password must be at least 8 characters.</small>
            </div>

            <div class="form-group">
                <label for="repeat_password">Confirm Password</label>
                <input
                    type="password"
                    id="repeat_password"
                    name="repeat_password"
                    placeholder="Re-enter your password"
                    required
                    autocomplete="new-password">
            </div>

            <div class="form-btn">
                <button type="submit" name="submit" class="btn">
                    Register
                </button>
            </div>

        </form>

        <p>
            Already registered?
            <a href="login.php">Login here</a>
        </p>

    </div>

</body>

</html>