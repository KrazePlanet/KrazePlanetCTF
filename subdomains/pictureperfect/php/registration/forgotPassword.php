<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- fav-icon -->
    <link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
    <?php include './links.php' ?>
    <title>Set New Password 🔑</title>
</head>

<body>
    <?php
    include('./dbcon.php');
    if (isset($_POST['submit'])) {
        $pass = $_POST['pass'];
        $cpass = $_POST['cpass'];
        $email = $_SESSION['email'];
        $passSearch = "select * from registration where email='$email'";
        $sql = mysqli_query($conn, $passSearch);
        $data = mysqli_fetch_assoc($sql);
        $db_pass = $data['password'];
        if ($pass === $cpass) {
            $check = password_verify($pass, $db_pass);
            if ($check) {
                $pass_error_msg = "password is same as old password";
            } else {
                $pass_hash = password_hash($pass, PASSWORD_BCRYPT);
                $updateQuery = "UPDATE registration SET password='$pass_hash' WHERE email='$email'";
                $sql = mysqli_query($conn, $updateQuery);
                $_SESSION['pass-message'] = "";
                header('location:login.php');
            }
        } else {
            $pass_error_msg = "password doesnot match";
        }
    }

    ?>
    <!-- loader html -->
    <div class="loader"></div>
    <!-- login form -->
    <div class="container">
        <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>" class="form signup">
            <h2>Set New Password</h2>
            <div class="inputBox">
                <input type="password" name="pass" id="pass" autocomplete="off" required="required"
                    onkeyup="validatingPassword()">
                <i class="fa-regular fa-envelope"></i>
                <span>New Password</span>
                <p class="pass-valid" id="pass-validation">
                    <?php if (isset($pass_error_msg)) {
                        echo $pass_error_msg;
                    } ?>
                </p>
            </div>
            <div class="inputBox">
                <input type="password" name="cpass" id="cpass" autocomplete="off" required="required">
                <i class="fa-regular fa-envelope"></i>
                <span>Confirm Password</span>
            </div>
            <div class="inputBox">
                <input type="submit" id="submit" name="submit" value="Reset Password" disabled>
            </div>
        </form>
    </div>

</body>

</html>