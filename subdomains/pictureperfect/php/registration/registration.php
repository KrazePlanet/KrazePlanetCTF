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
    <title>register your account</title>
</head>

<body>
    <?php
    include('./dbcon.php');
    if (isset($_POST['submit'])) {
        $uname = mysqli_real_escape_string($conn, $_POST['uname']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $pass = mysqli_real_escape_string($conn, $_POST['pass']);
        $cpass = mysqli_real_escape_string($conn, $_POST['cpass']);

        $token = bin2hex(random_bytes(15));

        $emailQuery = "select * from registration where email='$email'";
        $sql = mysqli_query($conn, $emailQuery);
        $emailno = mysqli_num_rows($sql);

        if ($emailno != 0) {
            $email_error_msg = "email already exist";
        } else {
            if ($pass === $cpass) {
                $pass = password_hash($pass, PASSWORD_BCRYPT);
                $insertQuery = "INSERT INTO registration (username,email,password,token,status,role) values ('$uname','$email','$pass','$token','inactive',0)";
                $sql = mysqli_query($conn, $insertQuery);

                if ($sql) {
                    $subject = "Email activation";
                    $body = "Hi,$uname . click on the link below to activate your account 
                    http://localhost/PICTURE%20PERFECT%20PROJECT/php/registration/activation.php?token=$token";
                    $my_email = "From: Pictureperfectcor@gmail.com";

                    if (mail($email, $subject, $body, $my_email)) {
                        $_SESSION['msg'] = "check your email ($email) to activate your account
                        if already done so then pls fill the form";
                        header('location:login.php');
                    } else {
                        echo "Email sending failed...";
                    }
                } else {
                    ?>
                    <script>
                        alert("some problem occured try again");
                    </script>
                    <?php
                }
            } else {
                $pass_error_msg = "password do not match";
            }
        }
    }
    ?>


    <!-- loader html -->
    <div class="loader"></div>
    <!-- SIGNUP FORM -->
    <div class="container">
        <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>" class="form signup">
            <h2>Sign Up</h2>
            <div class="inputBox">
                <input type="text" name="uname" id="uname" autocomplete="off"
                    value="<?php echo isset($_POST["uname"]) ? $_POST["uname"] : ''; ?>" required="required">
                <i class="fa-regular fa-user"></i>
                <span>username</span>

            </div>
            <div class="inputBox">
                <input type="text" name="email" id="email" autocomplete="off"
                    value="<?php echo isset($_POST["email"]) ? $_POST["email"] : ''; ?>" required="required"
                    onkeyup='validateEmail()'>
                <i class="fa-regular fa-envelope"></i>
                <span>email address</span>
                <p id="email-valid">
                    <?php if (isset($email_error_msg)) {
                        echo $email_error_msg;
                    } ?>
                </p>
            </div>
            <div class="inputBox">
                <input type="password" name="pass" autocomplete="off" id="pass" required="required"
                    onkeyup="validatePassword()">
                <i class="fa-solid fa-lock"></i>
                <span>create password</span>
                <p class="pass-valid" id="pass-valid">
                    <?php if (isset($pass_error_msg)) {
                        echo $pass_error_msg;
                    } ?>
                </p>
            </div>
            <div class="inputBox">
                <input type="password" name="cpass" autocomplete="off" id="cpass" required="required">
                <i class="fa-solid fa-lock"></i>
                <span>confirm password</span>
            </div>
            <div class="inputBox">
                <input type="submit" id="submit" name="submit" value="Create Account" disabled>
            </div>
            <p class="link-p">Already a member ? <a href="./login.php" class="login">Log in</a></p>
        </form>
    </div>
</body>

</html>