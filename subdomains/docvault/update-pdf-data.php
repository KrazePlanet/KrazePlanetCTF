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
$id = $_POST["id"];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
<style>
    form {
        display: flex !important;
        flex-direction: column;
        width: 70%;
        margin-top: 5.2vh;
        justify-content: space-between;
    }

    body {
        max-height: 100vh !important;
    }

    .select-dropdown {
        position: relative;
        height: 30px;
        width: 98%;
        border: 1px solid;
        border-image: linear-gradient(to right, #0c48db, #ffffff) 0 0 1 0%;
        background-color: #fff;
        font-size: 14px;
        font-family: "Roboto", sans-serif;
        margin-top: 0;
        padding-top: 0;
        top: -18px;
    }

    .select-dropdown__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 5px;
        cursor: pointer;

    }



    .select-dropdown__toggle {
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 5px 4px 0 4px;
        border-color: #333 transparent transparent transparent;
    }

    .select-dropdown__options {
        position: absolute;
        top: 100%;
        left: 0;
        z-index: 1;
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        background-color: #fff;
        border: 1px solid #ccc;
        border-top: none;
        display: none;
    }

    .select-dropdown__options label {
        display: block;
        padding: 5px 10px;
    }

    .select-dropdown__options input[type="checkbox"] {
        margin-right: 5px;
    }

    .over-all-box,
    .container {
        min-height: 60vh !important;
    }

    .container h1 {
        margin-bottom: 15px;
    }

    .back_button {
        border: none;
        background-color: transparent;
        padding: 11vh 0 0 2vw;
        position: absolute;
    }

    .actual-btn {
        background-color: #0c48db;
        color: #fff !important;
        padding: 3px;
        border-radius: 5px;
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
                <li><a href="view-only.php" style="color:#fff" class="active">Documents</a></li>
                <li><a href="useraccess.php">User Details</a></li>
                <li><a href="profile.php">Profile</a></li>
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
                <li><a href="useraccess.php" style="color:#fff" class="active">User Details</a></li>
                <li><a href="profile.php" class="">Profile</a></li>
                <li><a href="logout.php" class="join-button">Logout</a></li>
            </ul>
        </nav>
    <?php } ?>
    <!-- partial:index.partial.html -->
    <button onclick="history.back()" class="back_button"><img src="./assets/back_arrow.png"></button>

    <section class="over-all-box">
        <div class="container margin_top">
            <div id="logoo">
                <h1 class="logoo">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</h1>
            </div>
            <?php
            $sql = "SELECT * FROM `software_model` WHERE `id` = '$id'";
            #echo $sql;
            $result = mysqli_query($link, $sql);
            while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="rightbox">
                    <div class="profile">
                        <h1>
                            Update Details of <?php echo $row['drawing_number'] ?>
                        </h1><?php
                                if ($row['file'] == 'not_uploaded.pdf') {
                                    $desc = $row['description'];
                                ?>
                            <form action="action.php" id="myForm" method="post" enctype="multipart/form-data">
                                <label>Drawing Number</label>
                                <input type="text" class="form-control" id="drawing-number" name="drawing_number" placeholder="Enter Drawing Name" value="<?php echo $row['drawing_number'] ?>" readonly><br />
                                <label>Revision Number</label>
                                <input type="text" class="form-control" name="revision_number" placeholder="Enter Revision Number" value="<?php echo $row['revision_number'] ?>" required><br />
                                <label>Description</label>
                                <input type="text" class="form-control" name="description" placeholder="Enter Description" value='<?php echo $desc; ?>' required><br />
                                <br />
                                <div class="select-dropdown">
                                    <div class="select-dropdown__header">
                                        <div class="select-dropdown__title">Select Sector</div>
                                        <div class="select-dropdown__toggle"></div>
                                    </div>
                                    <div class="select-dropdown__options" selectedOptions[]>
                                        <?php
                                        $sector_sql = "SELECT * FROM `sectors` WHERE username = '$username'";
                                        $sector_result = mysqli_query($link, $sector_sql);
                                        $multi_sector = array();
                                        while ($sector_row = mysqli_fetch_assoc($sector_result)) {
                                            $sector = array_push($multi_sector, $sector_row['sector']);
                                        }
                                        $length = count($multi_sector);
                                        for ($i = 0; $i < $length; $i++) {
                                        ?>
                                            <label>
                                                <input type="checkbox" value='<?php echo $multi_sector[$i] ?>' />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $multi_sector[$i] ?>
                                            </label>
                                        <?php
                                            unset($multi_sector[$i]);
                                        } ?>
                                    </div>
                                </div>

                                <?php if (trim($admin) == 'SuperAdmin' or trim($admin) == 'Admin') { ?>
                                    <input style="display:none" type="text" name="id" value="<?php echo $row['id'] ?>">
                                    <input style="display:none" type="text" name="file" value="<?php echo $row['file'] ?>">
                                    <input style="display:none" type="text" name="delete-pdf" value="<?php echo $row['file'] ?>">
                                    <input style="display:none" type="text" name="sector" value="<?php echo $row['sector'] ?>">
                                    <div class="flex-row">
                                        <div style="margin-top: .5vh;" class="form-group">
                                            <input type="file" name="file" class="form-control" title="Upload PDF" id="actual-btn" hidden required />
                                            <label class="actual-btn" for="actual-btn">Choose File</label>
                                            <span id="file-chosen">No file chosen</span>
                                        </div>
                                        <button type="submit" class="btnRegister" name="update-pdf-details-file" onclick="return confirm('Are you sure you want to Submit?')" value="Submit">Update</button>
                                    </div>
                                <?php } ?>
                            </form>
                        <?php } else {
                                    $desc = $row['description'];
                        ?>
                            <form action="action.php" id="myForm" method="post">
                                <label>Drawing Number</label>
                                <input type="text" class="form-control" id="drawing-number" name="drawing_number" placeholder="Enter Drawing Name" value="<?php echo $row['drawing_number'] ?>" readonly><br />
                                <label>Revision Number</label>
                                <input type="text" class="form-control" name="revision_number" placeholder="Enter Revision Number" value="<?php echo $row['revision_number'] ?>" required><br />
                                <label>Description</label>
                                <input type="text" class="form-control" name="description" placeholder="Enter Description" value='<?php echo $desc ?>' required><br />
                                <br />
                                <div class="select-dropdown">
                                    <div class="select-dropdown__header">
                                        <div class="select-dropdown__title">Select Sector</div>
                                        <div class="select-dropdown__toggle"></div>
                                    </div>
                                    <div class="select-dropdown__options" selectedOptions[]>
                                        <?php
                                        $sector_sql = "SELECT * FROM `sectors` WHERE username = '$username'";
                                        $sector_result = mysqli_query($link, $sector_sql);
                                        $multi_sector = array();
                                        while ($sector_row = mysqli_fetch_assoc($sector_result)) {
                                            $sector = array_push($multi_sector, $sector_row['sector']);
                                        }
                                        $length = count($multi_sector);
                                        for ($i = 0; $i < $length; $i++) {
                                        ?>
                                            <label>
                                                <input type="checkbox" value="<?php echo $multi_sector[$i] ?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $multi_sector[$i] ?>
                                            </label>
                                        <?php
                                            unset($multi_sector[$i]);
                                        } ?>
                                    </div>
                                </div>
                                <?php if (trim($admin) == 'SuperAdmin' or trim($admin) == 'Admin') { ?>
                                    <input style="display:none" type="text" name="id" value="<?php echo $row['id'] ?>">
                                    <input style="display:none" type="text" name="file" value="<?php echo $row['file'] ?>">
                                    <input style="display:none" type="text" name="delete-pdf" value="<?php echo $row['file'] ?>">
                                    <input style="display:none" type="text" name="sector" value="<?php echo $row['sector'] ?>">
                                    <div class="flex-row">
                                        <button type="submit" class="btnRegister" name="update-pdf-details" onclick="return confirm('Are you sure you want to Submit?')" value="Submit">Update</button>
                                        <button type="submit" class="btnRegister delete" onclick="return confirm('Are you sure you want to Delete?')" name="delete-pdf-btn">Delete</button>
                                    </div>
                                <?php } ?>
                            </form>
                        <?php } ?>
                    </div>
                <?php
            }
                ?>
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

    const actualBtn = document.getElementById('actual-btn');

    const fileChosen = document.getElementById('file-chosen');

    actualBtn.addEventListener('change', function() {
        fileChosen.textContent = this.files[0].name
    })
</script>
<script type="text/javascript" src="https://pdfanticopy.com/noprint.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
    const form = document.querySelector('#myForm'); // replace 'myForm' with the ID of your form
    const dropdown = document.querySelector('.select-dropdown');
    const header = dropdown.querySelector('.select-dropdown__header');
    const toggle = header.querySelector('.select-dropdown__toggle');
    const options = dropdown.querySelector('.select-dropdown__options');
    const checkboxes = options.querySelectorAll('input[type="checkbox"]');

    function toggleOptions() {
        options.style.display = options.style.display === 'none' ? 'block' : 'none';
    }

    function handleCheckboxChange() {
        const selectedOptions = Array.from(checkboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);

        // remove previously added hidden input fields
        form.querySelectorAll('input[type="hidden"]').forEach(hiddenInput => {
            hiddenInput.parentNode.removeChild(hiddenInput);
        });

        // create new hidden input fields with the selected values and append to the form
        selectedOptions.forEach(option => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'selectedOptions[]'; // add [] to the name to pass the values as an array
            hiddenInput.value = option;
            form.appendChild(hiddenInput);
        });
    }

    header.addEventListener('click', toggleOptions);
    checkboxes.forEach(checkbox => checkbox.addEventListener('change', handleCheckboxChange));

    form.addEventListener('submit', (event) => {
        // submit the form using POST method
        const formData = new FormData(form);
        const selectedOptions = formData.getAll('selectedOptions[]');
        if (selectedOptions.length > 0) {
            // prevent the form from submitting normally if there are selected options
            fetch(form.action, {
                method: 'POST',
                body: formData
            }).then(response => {
                console.log(response);
                // handle the response as needed
            }).catch(error => {
                console.error(error);
                // handle the error as needed
            });
        }
    });
</script>


<script>
    // Get the drawing number input field
    const drawingNumberInput = document.querySelector('#drawing-number');

    // Get the select dropdown div
    const selectDropdown = document.querySelector('.select-dropdown');

    // Listen for changes to the drawing number input field
    drawingNumberInput.addEventListener('input', () => {
        // Disable the select dropdown div
        selectDropdown.disabled = true;
    });
</script>


</html>