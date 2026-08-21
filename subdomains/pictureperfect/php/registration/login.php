<?php session_start(); 
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- fav-icon -->
    <link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
    <?php include './links.php' ?>
    <title>Login to your account</title>
</head>

<body>
    <?php
    include('./dbcon.php');
    if (isset($_POST['submit'])) {
        $email = $_POST['email'];
        $pass = $_POST['pass'];

        $emailSearch = "select * from registration where email='$email' and status='active'";
        $sql = mysqli_query($conn, $emailSearch);

        $emailno = mysqli_num_rows($sql);

        if ($emailno) {
            $_SESSION['email'] = $email;
            $forgotPass = "Forgot Password. Dont worry <a style='color: white;' 
            href='./forgotPassemail.php'>Click here</a>";
            $data = mysqli_fetch_assoc($sql);
            $db_pass = $data['password'];
            $_SESSION['uname'] = $data['username'];
            $_SESSION['role']=$data['role'];
            $pass_decode = password_verify($pass, $db_pass);
            if ($pass_decode) {
                if (isset($_SESSION['site'])) {
                    if($_SESSION['site'] == "review"){
                        header('location:../review/review.php');
                    }elseif($_SESSION['site'] == "bookMe"){
                        header('location:../bookMe/bookMe.php');
                    }else{
                        header('location:../../index.php');
                    }
                }
            } else {
                $pass_error_msg = "password is invalid";
            }

        } else {
            $email_error_msg = "your email is invalid";
        }
    }
    ?>


    <!-- loader html -->
    <div class="loader"></div>
    <!-- login form -->
    <div class="container">

        <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>" class="form signup">
            <h2>Sign In</h2>
            <p class="session-msg">
                <?php if (isset($_SESSION['msg']) && isset($_SESSION['pass-message'])) {
                     echo $_SESSION['pass-message'];
                } elseif (isset($_SESSION['msg'])) {
                    echo $_SESSION['msg'];
                } else if(isset($_SESSION['pass-message'])) {
                    echo $_SESSION['pass-message'];
                } else{
                    echo "enter password to login";
                } ?>
            </P>
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
                <input type="password" name="pass" autocomplete="off" id="pass" required="required">
                <i class="fa-solid fa-lock"></i>
                <span>Enter Password</span>
                <p id="email-valid">
                    <?php if (isset($pass_error_msg)) {
                        echo $pass_error_msg;
                    } ?>
                </p>
            </div>
            <div class="inputBox">
                <input type="submit" id="submit" name="submit" value="Login to Account">
            </div>
            <p class="link-p">Already a member ? <a href="./registration.php" class="login">Sign Up</a></p>
            <p style="color: #00ffe1; font-size: .6em; margin-top: -20px; font-weight: 300; 
                    background-color: rgba(0, 0, 0, 0.562);
                    padding: 0.5em;
                    border-radius: 25px;">
                <?php
                if (isset($forgotPass)) {
                    echo $forgotPass;
                }
                ?></p>
        </form>
    </div>

</body>

</html>