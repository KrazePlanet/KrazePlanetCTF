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

$search_username = $row['username'];



$sector_1 = $_POST['sector-1'];
$sector_2 = $_POST['sector-2'];
$sector_3 = $_POST['sector-3'];
$sector_4 = $_POST['sector-4'];
if ($sector_1 != '') {
    $sector = $sector_1;
}
if ($sector_2 != '') {
    $sector = $sector_2;
}
if ($sector_3 != '') {
    $sector = $sector_3;
}
if ($sector_4 != '') {
    $sector = $sector_4;
}
$s_no = 1;




?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Whale Enterprises Pvt Ltd</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.png">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css'>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
    <script type="text/javascript" src="./js/script.js"></script>
    <script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
</head>
<style>
    body {
        background: linear-gradient(to right, #ecf0fb, #f5f6f8);
    }

    .container {
        min-width: fit-content;
    }

    .add_user_button {
        height: fit-content;
        border: 0px;
        cursor: pointer;
    }

    .back_button {
        border: none;
        background-color: transparent;
        padding: 11vh 0 0 2vw;
        position: absolute;
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
                <li><a href="#" style="color:#fff" class="active">Documents</a></li>
                <li><a href="useraccess.php" class="">User Details</a></li>
                <li><a href="profile.php" class="">Profile</a></li>
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
                <li><a href="#" style="color:#fff" class="active">Documents</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php" class="join-button">Logout</a></li>
            </ul>
        </nav>
    <?php } ?>
    <a href="index.php" class="back_button"><img src="./assets/back_arrow.png"></a>

    <section class="pdflist">
        <h1>View the Documents </h1>
        <div class="search-box mobile-view">
            <form class="search-box" action="" method="post">
                <input class="search-bar" type="text" name="filter-value" placeholder="Search Here!" />
                <button name="filter" class="search_details">Search</button>
            </form>
            <form action="">
                <button name="clear_filter" class="add_user_button">Clear</button>
            </form>
            <?php if (trim($admin) == 'SuperAdmin' or trim($admin) == 'Admin') { ?>
                <center style="margin-top: .8vh;">
                    <button id="downloadButton" class="add_user_button">Download</button>
                </center>
            <?php } ?>
        </div>
        <?php
        $sector_sql = "SELECT * FROM `sectors` WHERE username = '$username'";
        $sector_result = mysqli_query($link, $sector_sql);
        $multi_sector = array();
        while ($sector_row = mysqli_fetch_assoc($sector_result)) {
            array_push($multi_sector, $sector_row['sector']);
        }
        $length = count($multi_sector);
        ?>
        <?php
        if (isset($_COOKIE['status'])) {
            printf("<center><p style='color:green;'>" . $_COOKIE['status'] . "</p></center>");
        }
        ?><section class="container">
            <table>
                <thead class="visible@l">
                    <tr>
                        <th>S.No</th>
                        <th>Drawing Number</th>
                        <th>Revision Number</th>
                        <th>Description</th>
                        <th>Sector</th>
                        <th> </th>
                        <th> </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (isset($_POST['filter'])) {
                        $sector_sql = "SELECT * FROM `sectors` WHERE username = '$username'";
                        $sector_result = mysqli_query($link, $sector_sql);
                        $multi_sector = array();
                        while ($sector_row = mysqli_fetch_assoc($sector_result)) {
                            array_push($multi_sector, $sector_row['sector']);
                        }
                        array_push($multi_sector, 'Not Selected');
                        $length = count($multi_sector);
                        $value_filter = $_POST['filter-value'];
                        $value_filter = strtoupper($value_filter);
                        for ($i = 0; $i < $length; $i++) {
                            if ($sector == '') {
                                if ($value_filter != '') {
                                    $search_sql = "SELECT * from software_model WHERE UPPER(CONCAT(drawing_number, revision_number,`sector`, `description`)) LIKE '%{$value_filter}%' and sector = '$multi_sector[$i]' ORDER BY drawing_number ASC LIMIT 100";
                                    unset($multi_sector[$i]);
                                    echo "<br>";
                                } else {
                                    $search_sql = "SELECT * from software_model WHERE sector = '$multi_sector[$i]' ORDER BY drawing_number ASC";
                                    unset($multi_sector[$i]);
                                }
                            }
                            if ($sector != '') {
                                $search_sql = "SELECT * from software_model where CONCAT(drawing_number, revision_number,`sector`, `description`) LIKE '%$value_filter%' and sector = '$sector' ORDER BY drawing_number ASC LIMIT 100 COLLATE utf8mb4_unicode_ci";
                                echo $search_sql;
                                echo "hey";
                            }
                            $search_result = mysqli_query($link, $search_sql);
                            if (mysqli_num_rows($search_result) > 0) {
                                while ($search_row = mysqli_fetch_assoc($search_result)) { ?>
                                    <tr>
                                        <td><strong class="hidden@l">S.no</strong>&nbsp;
                                            <?php echo $s_no;
                                            $s_no += 1; ?>
                                        </td>
                                        <td><strong class="hidden@l">Drawing Number</strong>&nbsp;
                                            <?php echo $search_row['drawing_number']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Revision Number</strong>&nbsp;
                                            <?php echo $search_row['revision_number']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Description</strong>&nbsp;
                                            <?php echo $search_row['description']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Sector</strong>&nbsp;
                                            <?php echo $search_row['sector']; ?>
                                        </td>
                                        <td style='display:flex; flex-direction:row;'>
                                            <form action="file-view-only.php" method='POST' class="table-forms">
                                                <input type="hidden" name="id" value="<?php echo $search_row['id']; ?>" />
                                                <input type="hidden" name="filename" value="<?php echo $search_row['file']; ?>" />
                                                <button type="submit" class="update_details button" data-modal="modalOne" name="view-pdf">View</button>
                                            </form>
                                            <?php if (trim($admin) == 'SuperAdmin' or trim($admin) == 'Admin') { ?>
                                                <form action="update-pdf-data.php" method='POST' class="table-forms">
                                                    <input type="hidden" name="id" value="<?php echo $search_row['id']; ?>" />
                                                    <button type="submit" class="update_details" name="view-pdf">Edit</button>
                                                </form>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if (trim($admin) == 'SuperAdmin' or trim($admin) == 'Admin') { ?>
                                                <form action="action.php" method='POST' class="table-forms">
                                                    <input type="hidden" name="delete-pdf" value="<?php echo $search_row['file']; ?>" />
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
                                                    <button type="submit" onclick="return confirm('Are you sure you want to Delete?')" class="update_details red" name="delete-pdf-btn">Delete</button>
                                                </form> <?php } ?>
                                        </td>
                                    </tr>
                                <?php
                                }
                            }
                        }
                    }
                    if (isset($_POST['filter']) == false) {
                        $sector_sql = "SELECT * FROM `sectors` WHERE username = '$username'";
                        $sector_result = mysqli_query($link, $sector_sql);
                        $multi_sector = array();
                        while ($sector_row = mysqli_fetch_assoc($sector_result)) {
                            array_push($multi_sector, $sector_row['sector']);
                        }
                        array_push($multi_sector, "Not Selected");
                        $length = count($multi_sector);
                        for ($i = 0; $i < $length; $i++) {
                            if ($sector == '') {
                                $sql = "SELECT * FROM software_model WHERE sector='$multi_sector[$i]' ORDER BY id DESC LIMIT 100";
                                $result = mysqli_query($link, $sql);
                                unset($multi_sector[$i]);
                            }
                            if ($sector != '') {
                                $sql = "SELECT * FROM software_model WHERE sector='$sector' ORDER BY id DESC LIMIT 100";
                                $result = mysqli_query($link, $sql);
                                unset($multi_sector[$i]);
                                $length = 1;
                            }
                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) { ?>
                                    <tr>
                                        <td><strong class="hidden@l">S.no</strong>&nbsp;
                                            <?php echo $s_no;
                                            $s_no += 1; ?>
                                        </td>
                                        <td><strong class="hidden@l">Drawing Number</strong>&nbsp;
                                            <?php echo $row['drawing_number']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Revision Number</strong>&nbsp;
                                            <?php echo $row['revision_number']; ?>
                                        </td>
                                        <td><strong class="hidden@l">Description</strong>&nbsp;
                                            <?php echo $row['description']; ?>

                                        </td>
                                        <td><strong class="hidden@l">Sector</strong>&nbsp;
                                            <?php echo $row['sector']; ?>
                                        </td>
                                        <td style='display:flex; flex-direction:row;'>
                                            <form action="file-view-only.php" method='POST' class="table-forms">
                                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
                                                <input type="hidden" name="filename" value="<?php echo $row['file']; ?>" />
                                                <button type="submit" class="update_details button" data-modal="modalOne" name="view-pdf">View</button>
                                            </form>
                                            <?php if (trim($admin) == 'SuperAdmin' or trim($admin) == 'Admin') { ?>
                                                <form action="update-pdf-data.php" method='POST' class="table-forms">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
                                                    <button type="submit" class="update_details" name="view-pdf">Edit</button>
                                                </form>
                                            <?php } ?>
                                        </td>
                                        <td>
                                            <?php if (trim($admin) == 'SuperAdmin' or trim($admin) == 'Admin') { ?>
                                                <form action="action.php" method='POST' class="table-forms">
                                                    <input type="hidden" name="delete-pdf" value="<?php echo $row['file']; ?>" />
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
                                                    <button type="submit" onclick="return confirm('Are you sure you want to Delete?')" class="update_details red" name="delete-pdf-btn">Delete</button>
                                                </form> <?php } ?>
                                        </td>
                                    </tr>
                    <?php }
                            }
                        }
                    } ?>
                </tbody>
            </table>
        </section>
    </section>
</body>
<script src="./js/nav.js"></script>
<script src="./js/script.js"></script>
<script language="javascript">
    var noPrint = true;
    var noCopy = true;
    var noScreenshot = true;
    var autoBlur = false;
    $(document).ready(function() {
        $('#downloadButton').click(function() {
            // Make an AJAX call to the download.php script
            $.ajax({
                url: 'download.php',
                type: 'GET',
                success: function(data) {
                    // Create a temporary anchor element to initiate the file download
                    var link = document.createElement('a');
                    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(data);
                    link.download = 'data.csv';
                    link.target = '_blank';
                    link.click();
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                }
            });
        });
    });
</script>
<script type="text/javascript" src="https://pdfanticopy.com/noprint.js"></script>


</html>