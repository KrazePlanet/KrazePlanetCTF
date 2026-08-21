<?php
// Initialize the session
session_start();
require_once "config.php";

// Check if the user is already logged in, if yes then redirect him to welcome page
if (isset($_SESSION["whale_enterprises_loggedin"]) && $_SESSION["whale_enterprises_loggedin"] === true) {
    header("location: index.php");
    exit;
}
#error_reporting(0);
// Include config file

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter your Mail ID.";
    } else {
        $username = trim($_POST["username"]);
        // Prepare a select statement
        $sql = "SELECT username FROM `users` WHERE username = '$username'";
        $result = mysqli_query($link, $sql);
        if (mysqli_num_rows($result) == 1) {
            $password_result = exec("python ./python/password_reset.py" . " $username");
            if (!$password_result) {
                $_SESSION['login_err'] = "There is an unexpected error! Try again in few minutes";
            }
            $cookie_name = "password_username";
            setcookie($cookie_name, $username, time() + 3600); // 3600 = 1 hr
            header("Location: verify-otp.php");
        } else {
            $_SESSION['login_err'] = "Email ID not found";
        }
    }
    mysqli_close($link);

    // Close statement
} else { ?>
    <p style="text-align:center;">Please wait a moment or try again later</p>
<?php }
// Close connection

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Whale Enterprise | Reset Password</title>
    <link rel="stylesheet" href="./css/login-style.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.png">
    <link rel="icon" type="image/x-icon" href="./assets/logo.png">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />

</head>

<body>
    <!-- partial:index.partial.html -->
    <section>
        <div class="login-root">
            <div class="box-root flex-flex flex-direction--column" style="min-height: 100vh;flex-grow: 1;">
                <div class="loginbackground box-background--white padding-top--64">
                    <div class="loginbackground-gridContainer">
                        <div class="box-root flex-flex" style="grid-area: top / start / 8 / end;">
                            <div class="box-root" style="background-image: linear-gradient(white 0%, rgb(247, 250, 252) 33%); flex-grow: 1;">
                            </div>
                        </div>
                        <div class="box-root flex-flex" style="grid-area: 4 / 2 / auto / 5;">
                            <div class="box-root box-divider--light-all-2 animationLeftRight tans3s" style="flex-grow: 1;"></div>
                        </div>
                        <div class="box-root flex-flex" style="grid-area: 6 / start / auto / 2;">
                            <div class="box-root box-background--blue800" style="flex-grow: 1;"></div>
                        </div>
                        <div class="box-root flex-flex" style="grid-area: 7 / start / auto / 4;">
                            <div class="box-root box-background--blue animationLeftRight" style="flex-grow: 1;"></div>
                        </div>
                        <div class="box-root flex-flex" style="grid-area: 8 / 4 / auto / 6;">
                            <div class="box-root box-background--gray100 animationLeftRight tans3s" style="flex-grow: 1;"></div>
                        </div>
                        <div class="box-root flex-flex" style="grid-area: 2 / 15 / auto / end;">
                            <div class="box-root box-background--cyan200 animationRightLeft tans4s" style="flex-grow: 1;"></div>
                        </div>
                        <div class="box-root flex-flex" style="grid-area: 3 / 14 / auto / end;">
                            <div class="box-root box-background--blue animationRightLeft" style="flex-grow: 1;"></div>
                        </div>
                        <div class="box-root flex-flex" style="grid-area: 4 / 17 / auto / 20;">
                            <div class="box-root box-background--gray100 animationRightLeft tans4s" style="flex-grow: 1;"></div>
                        </div>
                        <div class="box-root flex-flex" style="grid-area: 5 / 14 / auto / 17;">
                            <div class="box-root box-divider--light-all-2 animationRightLeft tans3s" style="flex-grow: 1;"></div>
                        </div>
                    </div>
                </div>
                <div class="box-root padding-top--24 flex-flex flex-direction--column" style="flex-grow: 1; z-index: 9;">
                    <div class="box-root padding-top--48 padding-bottom--24 flex-flex flex-justifyContent--center">
                        <h1><a>Whale | Reset Password</a></h1>
                    </div>
                    <div class="box-root flex-flex flex-justifyContent--center error">
                    </div>
                    <div class="formbg-outer">
                        <div class="formbg">
                            <div class="formbg-inner padding-horizontal--48">
                                <span class="padding-bottom--15">Forgot your Password?</span>

                                <form id="stripe-login" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <?php
                                    if (!empty($_SESSION['login_err'])) {
                                        echo '<center><div style="color:red" class="alert alert-danger">' . $_SESSION['login_err'] . '</div></center>';
                                        $_SESSION['login_err'] = null;
                                    }
                                    ?>
                                    <div class="field padding-bottom--24">
                                        <label for="username">Mail ID</label>
                                        <input autocomplete="off" type="email" name="username" placeholder="Enter Mail ID" required class="form-control  <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                    </div>
                                    <span style="color:red" class="alert alert-danger" class="invalid-feedback"><?php echo $username_err; ?></span>
                                    <div class="field padding-bottom--24">
                                        <input style="background: blue;" name="send-otp-btn" type="submit" value="Send OTP">
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="footer-link padding-top--24">
                            <span>Don't Have an account? <a href="#" style="color: blue; cursor: pointer;"> Contact Administrator</a></span>
                            <div class="listing padding-top--24 padding-bottom--24 flex-flex center-center">
                                <span><a href="#">Contact</a></span>
                                <span><a href="#">Privacy & terms</a></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- partial -->

</body>
<script src="./js/script.js"></script>
<script language="javascript">
    var noPrint = true;
    var noCopy = true;
    var noScreenshot = true;
    var autoBlur = false;
</script>
<script type="text/javascript" src="https://pdfanticopy.com/noprint.js"></script>

</html>