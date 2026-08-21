<?php
include 'config.php';

session_start();
// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["whale_enterprises_loggedin"]) || $_SESSION["whale_enterprises_loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$usernamee = $_SESSION["username"];
error_reporting(0); // For not showing any error
$sql = "SELECT * FROM users Where username IN ('$usernamee')";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);
if ($row['username'] == null) {
    header('Location: logout.php');
    exit();
}
$admin = trim($row['designation']);

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
                                header("location: profile.php");
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
    <title>Whale Enterprises Pvt Ltd</title>
    <link rel="stylesheet" href="./css/profile.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.png">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css'>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.0.10/css/all.css'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prefixfree/1.0.7/prefixfree.min.js"></script>

</head>

<body>
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
                <li><a href="useraccess.php">User Details</a></li>
                <li><a href="#" style="color:#fff" class="active">Profile</a></li>
                <li><a href="logout.php" class="join-button">Logout</a></li>
            </ul>
        </nav>
    <?php }
    if (trim($admin) == 'User') { ?>

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
                <li><a href="#" style="color:#fff" class="active">Profile</a></li>
                <li><a href="logout.php" class="join-button">Logout</a></li>
            </ul>
        </nav>
    <?php } ?>
    <!-- partial:index.partial.html -->
    <section class="over-all-box">
        <div style="height:440px;" class="container">
            <div id="logoo">
                <h1 class="logoo">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h1>
            </div>
            <div class="leftbox">
                <navy>
                    <a id="profile" class="active_now"><i class="fa fa-user"></i></a>
                    <a id="payment"><i class="fa fa-edit"></i></a>
                    <a id="settings"><i class="fa fa-key"></i></a>
                </navy>
            </div>
            <?php
            $sector_sql = "SELECT * FROM `sectors` WHERE username = '$usernamee'";
            $sector_result = mysqli_query($link, $sector_sql);
            $multi_sector = '';
            while ($sector_row = mysqli_fetch_assoc($sector_result)) {
                $multi_sector = $multi_sector . $sector_row['sector'] . ' , ';
            }

            $sql = "SELECT * FROM `users` WHERE username = '$usernamee'";
            $result = mysqli_query($link, $sql);
            if (mysqli_num_rows($result) > 0) {
                $s_no = 1;
                while ($row = mysqli_fetch_assoc($result)) { ?>
                    <div class="rightbox">
                        <div class="profile">
                            <h1>Personal Info</h1>
                            <h2>Mail ID</h2>
                            <p><?php echo $row['username'] ?></p>
                            <h2>Full Name</h2>
                            <p><?php echo $row['name'] ?></p>
                            <h2>Sector</h2>
                            <p><?php echo rtrim($multi_sector, ", "); ?></p>
                            <h2>Phone Number</h2>
                            <p><?php echo $row['phone_number'] ?></p>
                            <h2>Designation</h2>
                            <p><?php echo $row['designation'] ?></p>
                            <?php
                            $check_sector_sql = "SELECT * FROM `sectors` WHERE username = '$usernamee'";
                            $check_sector_result = mysqli_query($link, $check_sector_sql);
                            if (mysqli_num_rows($check_sector_result) < 1) {
                                echo "<p style='text-align:center; color:red; border-image:none;border:none;'>Contact Admin to allocate sector</p>";
                            }
                            ?>
                        </div>
                <?php
                }
            }
                ?>

                <div class="settings noshow">
                    <h1 style="margin-bottom: 1vh;">Reset Your Password</h1>
                    <div class="update-details">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                            <label>Enter new password</label>
                            <input type="password" name="password" autocomplete="off" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>" />
                            <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            <br />
                            <label>Re-enter new password</label>
                            <input type="password" name="confirm_password" autocomplete="off" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>" />
                            <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            <br />
                            <br />
                            <br />
                            <button name="password-reset" type="submit" value="submit">Update</button>
                        </form>
                    </div>
                </div>
                <div class="payment noshow">
                    <h1>Personal Info Update</h1>
                    <?php
                    $sql = "SELECT * FROM `users` WHERE username = '$usernamee'";
                    $result = mysqli_query($link, $sql);
                    $row = mysqli_fetch_assoc($result) ?>
                    <form action="action.php" method="post">
                        <label>Full Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo $row['name'] ?>" required />
                        <br />
                        <label>Phone Number</label>
                        <input type="text" class="form-control" name="phone_number" value="<?php echo $row['phone_number'] ?>" required />
                        <br />
                        <br />
                        <button name="profile-update-details" type="submit" value="submit">Update</button>
                    </form>
                </div>
                    </div>
        </div>
    </section>
    <!-- partial -->
    <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js'></script>
    <script src="./js/script.js"></script>
    <script src="./js/nav.js"></script>

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