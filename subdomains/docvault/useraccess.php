<?php

include 'config.php';

session_start();
// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["whale_enterprises_loggedin"]) || $_SESSION["whale_enterprises_loggedin"] !== true) {
    header("location: login.php");
    exit;
}
$username = $_SESSION["username"];
error_reporting(0); // For not showing any error



$sql = "SELECT * FROM users Where username IN ('$username')";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);
if ($row['username'] == null) {
    header('Location: logout.php');
    exit();
}
$admin = trim($row['designation']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="./css/style.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.png">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css'>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script type="text/javascript" src="./js/script.js"></script>
</head>
<style>
    body {
        background: linear-gradient(to right, #ecf0fb, #f5f6f8);
    }

    .userlist {
        transform: scale(.95);
    }

    .add_user_button {
        font-size: 14px;
        background-color: #0c48db;
        text-decoration: none;
        color: #fff;
        padding: 10px;
        border-radius: 4px;
        margin-left: 2vw;
    }
</style>

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
                <li><a href="#" style="color:#fff" class="active">User Details</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php" class="join-button">Logout</a></li>
            </ul>
        </nav>
        <section class="userlist">
            <?php
            if (isset($_COOKIE['status'])) {
                printf("<center><p>" . $_COOKIE['status'] . "</p></center>");
            }
            ?>
            <h1>User Access Control</h1>
            <div class="search-box mobile-view">
                <form class="search-box" action="" method="post">
                    <input class="search-bar" type="text" name="filter-value" placeholder="Search Here!" />
                    <button name="filter" class="search_details">Search</button>
                </form>
                <center style="margin-top: 1.7vh;">
                    <a class="add_user_button" href="register.php">Add User</a>
                </center>
            </div>
            <section class="container">
                <table>
                    <thead class="visible@l">
                        <tr>
                            <th>S.No</th>
                            <th>Email ID</th>
                            <th>Name</th>
                            <th>Sector</th>
                            <th>Phone Number</th>
                            <th>Designation</th>
                            <th> </th>
                        </tr>
                    </thead>
                    <tbody>

                        <?php
                        if (isset($_POST['filter'])) {
                            $value_filter = $_POST['filter-value'];
                            if (trim($admin) == 'Admin') {
                                $search_sql = "SELECT * from users where CONCAT(username, name, sector, phone_number, designation) LIKE '%$value_filter%' and username != '$username' and designation NOT LIKE '%SuperAdmin%' and designation NOT LIKE '%Admin'";
                            } else {
                                $search_sql = "SELECT * from users where CONCAT(username, name, sector, phone_number, designation) LIKE '%$value_filter%' and username != '$username' and designation NOT LIKE '%SuperAdmin%'";
                            }
                            $search_result = mysqli_query($link, $search_sql);
                            if (mysqli_num_rows($search_result) > 0) {
                                $s_no = 1;
                                while ($search_row = mysqli_fetch_assoc($search_result)) {
                                    $search_username = $search_row['username'];
                                    $sector_sql = "SELECT * FROM `sectors` WHERE username = '$search_username'";
                                    $sector_result = mysqli_query($link, $sector_sql);
                                    $multi_sector = '';
                                    while ($sector_row = mysqli_fetch_assoc($sector_result)) {
                                        $multi_sector = $multi_sector . $sector_row['sector'] . ' , ';
                                    }
                        ?>
                                    <tr id='display'>
                                        <td><strong class="hidden@l">S.no</strong>&nbsp;
                                            <?php echo $s_no;
                                            $s_no += 1; ?>
                                        </td>
                                        <td><strong class="hidden@l">Email ID</strong>&nbsp;
                                            <?php echo $search_row['username']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Name</strong>&nbsp;
                                            <?php echo $search_row['name']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Sector</strong>&nbsp;
                                            <?php echo rtrim($multi_sector, ", "); ?>
                                        </td>
                                        <td><strong class="hidden@l">Phone Number</strong>&nbsp;
                                            <?php echo $search_row['phone_number']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Designation</strong>&nbsp;
                                            <?php echo $search_row['designation']; ?>
                                        </td>
                                        <td>
                                            <form action="update-details.php" method='POST' class="table-forms">
                                                <input style="cursor: pointer;" type="hidden" name="userid" value="<?php echo $search_row['username']; ?>" />
                                                <button type="submit" class="update_details" data-modal="modalOne" name="update_details">View</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php
                                }
                            } else { ?>
                                <tr id='display'>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td>
                                        <p>No Record Found</p>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                <?php
                            }
                        }
                        if (isset($_POST['filter']) == false) {
                            if (trim($admin) == 'SuperAdmin') {
                                $sql = "SELECT * FROM users WHERE username != '$username'  and designation != 'SuperAdmin' ORDER BY id DESC";
                            } else {
                                $sql = "SELECT * FROM users WHERE username != '$username'  and designation != 'SuperAdmin' and designation != 'Admin' ORDER BY id DESC";
                            }
                            $result = mysqli_query($link, $sql);
                            if (mysqli_num_rows($result) > 0) {
                                $s_no = 1;
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $search_username = $row['username'];
                                    $sector_sql = "SELECT * FROM `sectors` WHERE username = '$search_username'";
                                    $sector_result = mysqli_query($link, $sector_sql);
                                    $multi_sector = '';
                                    while ($sector_row = mysqli_fetch_assoc($sector_result)) {
                                        $multi_sector = $multi_sector . $sector_row['sector'] . ' , ';
                                    } ?>
                                    <tr id='display'>
                                        <td><strong class="hidden@l">S.no</strong>&nbsp;
                                            <?php echo $s_no;
                                            $s_no += 1; ?>
                                        </td>
                                        <td><strong class="hidden@l">Email ID</strong>&nbsp;
                                            <?php echo $row['username']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Name</strong>&nbsp;
                                            <?php echo $row['name']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Sector</strong>&nbsp;
                                            <?php echo rtrim($multi_sector, ", "); ?>

                                        </td>
                                        <td><strong class="hidden@l">Phone Number</strong>&nbsp;
                                            <?php echo $row['phone_number']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Designation</strong>&nbsp;
                                            <?php echo $row['designation']; ?>
                                        </td>
                                        <td data-th="">
                                            <form action="update-details.php" method='POST' class="table-forms">
                                                <input type="hidden" name="userid" value="<?php echo $row['username']; ?>" />
                                                <button type="submit" class="update_details button" data-modal="modalOne" name="update_details">View</button>
                                            </form>
                                        </td>
                                    </tr>
                        <?php }
                            }
                        } ?>
                    </tbody>
                </table>
            </section>
        </section>
    <?php } ?>
</body>
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