<?php
// Include config file
require_once "config.php";
session_start();
// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["whale_enterprises_loggedin"]) || $_SESSION["whale_enterprises_loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$usernamee = $_SESSION["username"];

$sql = "SELECT * FROM users Where username IN ('$usernamee')";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);
$admin = trim($row['designation']);

if (trim($row['designation']) == "Admin" or trim($row['designation']) == "SuperAdmin") {
    error_reporting(0);
    $username = $password = $confirm_password = "";
    $username_err = $password_err = $confirm_password_err = "";


    // Processing form data when form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate username
        if (empty(trim($_POST["username"]))) {
            $username_err = "Please enter your Mail ID.";
        } else {
            // Prepare a select statement
            $username = trim($_POST["username"]);

            $sql = "SELECT id FROM users WHERE username = '$username'";
            if (True) {
                $stmt = mysqli_prepare($link, $sql);
                // Bind variables to the prepared statement as parameters
                $param_username = trim($username);

                if (mysqli_stmt_execute($stmt)) {
                    /* store result */
                    mysqli_stmt_store_result($stmt);

                    if (mysqli_stmt_num_rows($stmt) == 1) {
                        $username_err = "This Mail ID is already exists.";
                    } else {
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

                                $param_username = $username;
                                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                                $sql = "INSERT INTO users (username, password) VALUES ('$username','$param_password')";
                                $stmt = mysqli_prepare($link, $sql);
                                // Attempt to execute the prepared statement
                                if (mysqli_stmt_execute($stmt)) {
                                    // Redirect to login page
                                    session_start();

                                    // Store data in session variables
                                    $_SESSION["Success"] = "You have successfully Registered!";
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
            mysqli_stmt_close($stmt);
        }
    }



    // Close connection
    mysqli_close($link);

?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Whale Enterprise | Signup</title>
        <link rel="stylesheet" href="./css/login-style.css">
        <link rel="stylesheet" href="./css/profile.css">
        <link rel="icon" type="image/x-icon" href="./assets/logo.png">

    </head>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap");

        .login-root {
            max-height: 100vh;
        }

        nav,
        .logo h1 {
            font-weight: 500;
            font-size: 17px;
        }

        .logo h1 {
            font-size: 20px;
            letter-spacing: .5px;
        }
    </style>

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
                    <div class="box-root flex-flex flex-direction--column" style="flex-grow: 1; z-index: 9;">
                        <?php if (trim($admin) == 'SuperAdmin' or trim($admin) == 'Admin') { ?>

                            <nav>
                                <div class="logo">
                                    <img class="logo-c" src=".\assets\logo.png">
                                    <h1>Whale Enterprises Pvt Ltd.</h1>
                                </div>
                                <div class="hamburger">
                                    <div class="line1"></div>
                                    <div class="line2"></div>
                                    <div class="line3"></div>
                                </div>
                                <ul class="nav-links">
                                    <li><a href="index.php">Home</a></li>
                                    <li><a href="view-only.php">Documents</a></li>
                                    <li><a href="useraccess.php" style="color:#fff" class="active">User Details</a></li>
                                    <li><a href="profile.php">Profile</a></li>
                                    <li><a href="logout.php" class="join-button">Logout</a></li>
                                </ul>
                            </nav>
                        <?php } ?>
                        <div class="box-root padding-top--48 padding-bottom--24 flex-flex flex-justifyContent--center">
                            <h1 style="margin-top: 6vh;"><a>Whale | User Register</a></h1>
                        </div>
                        <div class="box-root flex-flex flex-justifyContent--center error">
                        </div>
                        <div class="formbg-outer">
                            <div class="formbg">
                                <div class="formbg-inner padding-horizontal--48">
                                    <span class="padding-bottom--15">Add employee to the team.</span>
                                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                        <div class="field padding-bottom--24">
                                            <label for="username">Mail ID</label>
                                            <input class="input_box_reg" type="text" name="username" placeholder="Enter Mail ID" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                        </div>
                                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                                        <div class="field padding-bottom--24">
                                            <label for="password">Password</label>
                                            <input type="password" name="password" placeholder="Enter password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                                        </div>
                                        <div class="field padding-bottom--24">
                                            <label for="password">Re-enter Password</label>
                                            <input type="password" name="confirm_password" placeholder="Re-enter password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                                        </div>

                                        <div class="field padding-bottom--24">
                                            <input style="background: blue;" type="submit" class="login_btn" value="Register">
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="footer-link padding-top--24">
                                <span>Go back to User Access, <a href="useraccess.php" style="color: blue; cursor: pointer;"> Click here!</a></span>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- partial -->

    </body>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js'></script>
    <script src="./js/script.js"></script>
    <script src="./js/nav.js"></script>
    <script src="./js/script.js"></script>
    <script language="javascript">
        var noPrint = true;
        var noCopy = true;
        var noScreenshot = true;
        var autoBlur = false;
    </script>
    <script type="text/javascript" src="https://pdfanticopy.com/noprint.js"></script>

    </html>
<?php } ?>