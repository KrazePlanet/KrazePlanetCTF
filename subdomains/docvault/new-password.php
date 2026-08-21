<?php

include 'config.php';

session_start();
// Check if the user is logged in, if not then redirect him to login page

if (!isset($_COOKIE["password_username"])) {
    header("location: verify-otp.php");
} else {
    $usernamee = $_COOKIE["password_username"];
}
//error_reporting(0); // For not showing any error
$sql = "SELECT * FROM users Where username IN ('$usernamee')";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);


$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = "";


// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate username
    if ($username != $username) {
        $username_err = "Please enter your Mail ID.";
    } else {
        // Prepare a select statement
        $sql = "SELECT * FROM users WHERE username = '$usernamee'";

        if (True) {
            $stmt = mysqli_prepare($link, $sql);
            // Bind variables to the prepared statement as parameters
            $param_username = trim($usernamee);

            if (mysqli_stmt_execute($stmt)) {
                /* store result */
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    if (empty(trim($_POST["password"]))) {
                        $password_err = "Please enter a password.";
                    } elseif (strlen(trim($_POST["password"])) < 6) {
                        $password_err = "Password must have atleast 6 characters.";
                    } else {
                        $password = trim($_POST["password"]);
                    }

                    // Validate confirm password
                    if (empty(trim($_POST["confirm_password"]))) {
                        $confirm_password_err = "Please confirm password.";
                    } else {
                        $confirm_password = trim($_POST["confirm_password"]);
                        if (empty($password_err) && ($password != $confirm_password)) {
                            $confirm_password_err = "Password did not match.";
                        }
                    }

                    // Check input errors before inserting in database
                    if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
                        // Prepare an insert statement
                        if (True) {

                            $param_username = $usernamee;
                            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                            $sql = "UPDATE users SET password = '$param_password' WHERE username = '$usernamee'";
                            $stmt = mysqli_prepare($link, $sql);
                            // Attempt to execute the prepared statement
                            if (mysqli_stmt_execute($stmt)) {
                                // Redirect to login page
                                session_start();

                                // Store data in session variables
                                $_SESSION["Success"] = "You have successfully reset your Password!";
                                header("location: login.php");
                            } else {
                                echo "Oops! Something went wrong. Please try again later.";
                            }

                            // Close statement
                            mysqli_stmt_close($stmt);
                        }
                    }
                }
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }

        // Close statement

    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Whale Enterprise | Login</title>
    <link rel="stylesheet" href="./css/login-style.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />

</head>

<body>
    <!-- partial:index.partial.html -->
    <section>
        <div class="login-root">
            <div class="box-root flex-flex flex-direction--column" style="min-height: 100vh;flex-grow: 1;">

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

                                <form id="stripe-login" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                                    <div class="field padding-bottom--24">
                                        <label for="password">New Password</label>
                                        <input type="password" name="password" autocomplete="off" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>" />
                                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                                    </div>
                                    <div class="field padding-bottom--24">
                                        <label for="password">Re-enter Password</label>
                                        <input type="password" name="confirm_password" autocomplete="off" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>" />
                                        <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                                    </div>
                                    <div class="field padding-bottom--24">
                                        <input style="background: blue;" type="submit" value="Reset">
                                    </div>
                                    <input type="hidden" name='self_user' value="<?php echo $username; ?>">
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