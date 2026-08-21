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

$username = $_SESSION["username"];
$sql = "SELECT * FROM users Where username IN ('$username')";
$result = mysqli_query($link, $sql);
$row = mysqli_fetch_assoc($result);
if ($row['username'] == null) {
    header('Location: logout.php');
    exit();
}

$admin = trim($row['designation']);
$personal_name = $row['name'];
$personal_number = $row['phone_number'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Whale Enterprises Pvt Ltd</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="stylesheet" href="./css/profile.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.png">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css'>
    <script src="./js/prefixfree.min.js"></script>
</head>
<style>
    .container {
        display: none;
        position: fixed;
        z-index: 8;
        left: 0;
        top: -25vh;
        min-width: 100vw !important;
        height: 119vh;
        overflow: hidden;
        background-color: rgba(0, 0, 0, 0.4);
        color: #171717;
        padding-top: 38vh;
        overflow-x: hidden;
    }

    .form-input input {
        font-size: 13px;
        font-family: "Roboto", sans-serif !important;
        border-image: linear-gradient(to right, #0c48db, #ffffff) 0 0 1 0%;

    }

    .form-group label {
        background-color: indigo;
        color: white;
        padding: 0.5rem;
        font-family: sans-serif;
        border-radius: 0.3rem;
        cursor: pointer;
        margin-top: 1rem;
        color: #fff;
    }

    @media only screen and (max-width: 500px) {
        .contact-form {
            max-width: 80vw;
            margin: auto;
        }
    }


    .select-dropdown {
        position: relative;
        height: 30px;
        width: 98%;
        border: 1px solid;
        border-image: linear-gradient(to right, #0c48db, #ffffff) 0 0 1 0%;
        background-color: #fff;
        font-size: 13px;
        font-family: "Poppins", sans-serif;
        margin-top: 0;
        padding-top: 10px;
    }

    .select-dropdown__header {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 5px;
        cursor: pointer;
        top: -8px;

    }

    .select-dropdown__title {
        color: #747474;
        font-size: 13px;
        font-family: "Roboto", sans-serif !important;
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
        z-index: 100;
        width: 100% !important;
        max-height: 200px !important;
        overflow-y: scroll !important;
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
        display: block;
        position: absolute;
        left: -47%;
    }


    .admin-replace-documents {
        min-height: 35px;
        padding: 0 15vw 0 15vw;
        background-color: #eb6784;
        color: #fff;
        border-radius: 5px;
        border: 0px;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 10vh;
        cursor: pointer;
        margin-bottom: 5vh;
    }

    .upload-button-flex {
        display: flex;
        flex-direction: row;
        justify-content: space-around;
    }

    @media only screen and (max-width: 1230px) {
        .upload-button-flex {
            flex-direction: column;
        }

        .admin-replace-documents {
            margin-top: 0;

        }
    }

    #popup {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
    }

    #popup h2,
    #popup p {
        color: #000;
    }

    #close-popup {
        color: #000000;
        float: right;
        font-size: 28px;
        font-weight: bold;
        transition: 0.2s;
    }

    #close-popup:hover,
    #close-popup:focus {
        color: #3f3f3f;
        text-decoration: none;
        cursor: pointer;
        transition: 0.2s;
    }

    #popup h1 {
        font-size: 18px;
        color: #0c48db;
        font-weight: 200;
        margin: 5vh 0 5vh 0;
    }

    .file_choose,
    #file-chosen {
        padding: 0.5rem;
        font-family: sans-serif;
        border-radius: 0.3rem;
        cursor: pointer;
        font-size: 9px !important;
        letter-spacing: 1px;
    }

    #file-chosen {
        font-size: 12px !important;
    }
</style>

<body>
    <!-- partial:index.partial.html -->
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
                <li><a href="#home" style="color:#fff" class="active">Home</a></li>
                <li><a href="view-only.php">Documents</a></li>
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
                <li><a href="#home" style="color:#fff" class="active">Home</a></li>
                <li><a href="view-only.php">Documents</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php" class="join-button">Logout</a></li>
            </ul>
        </nav>
    <?php } ?>
    <section id="home" class="section">
        <h3 class="admin-sectors-title">Sectors for You</h3>
        <div class="overall">
            <?php
            $display_sector_sql = "SELECT * FROM sectors WHERE username = '$username'";
            $display_sector_result = mysqli_query($link, $display_sector_sql);
            while ($display_sector_row = mysqli_fetch_assoc($display_sector_result)) {
                if (trim($display_sector_row['sector']) == "Parts") { ?>
                    <form action="view-only.php" method="POST">
                        <input type="hidden" name="sector-1" value="Parts">
                        <button name="parts-btn">
                            <div class="main blue-border">
                                <div id="parts" class="out_body"></div>
                                <p>Parts</p>
                            </div>
                        </button>
                    </form>
                <?php }
                if (trim($display_sector_row['sector']) == "Fabrication") { ?>
                    <form action="view-only.php" method="POST">
                        <input type="hidden" name="sector-2" value="Fabrication">
                        <button name="tankers-btn">
                            <div class="main green-border">
                                <div id="tankers" class="out_body"></div>
                                <p>Fabrication</p>
                            </div>
                        </button>
                    </form>
            <?php }
            }
            ?>
        </div>
        <?php
        if (trim($admin) != 'User') { ?>
            <div class="upload-button-flex">
                <button class="admin-upload-documents button" data-modal="modalOne"><i class="fa fa-upload" aria-hidden="true"></i> &nbsp; Upload New Documents</button>
                <button class="admin-replace-documents button" id="open-popup"><i class="fa fa-exchange" aria-hidden="true"></i> &nbsp; Replace Documents</button>
            </div>
            <div id="popup">
                <div style="padding-top:15vh;" class="modal-content">
                    <div class="contact-form">
                        <span id="close-popup">&times;</span>
                        <form action="action.php" method="post" enctype="multipart/form-data">
                            <h1>Replace Document</h1>
                            <div class="form-input py-2">
                                <div class="form-group">
                                    <input type="text" class="form-control" name="d_no" placeholder="Enter Drawing Number" required>
                                </div>
                                <br />
                                <div class="form-group">
                                    <input type="text" class="form-control" name="r_no" placeholder="Enter Revision Number" required>
                                </div>
                                <br />
                                <div class="form-group">
                                    <input type="file" name="file" class="form-control" title="Upload PDF" id="actual-btn" hidden required />
                                    <label class="file_choose" for="actual-btn">CHOOSE FILE</label>
                                    <span id="file-chosen">No file chosen</span>
                                </div>
                                <br />
                                <div style="display:flex;align-items:center; justify-content:center;" class="form-group">
                                    <input type="submit" class="btnRegister" name="replace_submit" value="Submit">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php }
        if ($personal_number == null or $personal_name == null) { ?>
            <p style='margin:10vh 0vh 10vh 0vh;font-family:Montserrat;text-align:center; color:red; border-image:none;border:none;'>Kindly fill your details in <a style="font-family: Montserrat;" href='./profile.php'>profile</a></p>
        <?php } ?>
        <div id="modalOne" class="container">
            <div class="modal-content">
                <div class="contact-form">
                    <a class="close">&times;</a>
                    <form action="action.php" method="post" id="myForm" enctype="multipart/form-data">
                        <h1>Upload Documents</h1>
                        <div class="form-input py-2">
                            <div class="form-group">
                                <input type="text" class="form-control" name="d_no" placeholder="Enter Drawing Number" required>
                            </div>
                            <br />
                            <div class="form-group">
                                <input type="text" class="form-control" name="r_no" placeholder="Enter Revision Number" required>
                            </div>
                            <br />
                            <div class="form-group">
                                <input type="text" class="form-control" name="desc" placeholder="Description" required>
                            </div>
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
                            <br />
                            <div class=" form-group">
                                <input type="file" name="file" class="form-control" title="Upload PDF" id="actual_btn" hidden required />
                                <label for="actual_btn">Choose File</label>
                                <span id="file_chosen">No file chosen</span>
                            </div>
                            <br />

                            <div style="display:flex;align-items:center; justify-content:center;" class="form-group">
                                <input type="submit" class="btnRegister" name="submit" value="Submit">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        if (isset($_COOKIE['status'])) {
            printf("<center><p style='color:green;'>" . $_COOKIE['status'] . "</p></center>");
        }
        ?>
    </section>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.5.1/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.5.1/ScrollTrigger.min.js"></script>

    <script type="text/javascript">
        gsap.to(".filled-text, .outline-text", {
            scrollTrigger: {
                trigger: ".filled-text, .outline-text",
                start: "top bottom",
                end: "bottom top",
                scrub: 1
            },
            x: 200
        })

        gsap.to(".scroll-image", {
            scrollTrigger: {
                trigger: ".scroll-image",
                start: "top bottom",
                end: "bottom top",
                scrub: 1,
            },
            x: -250,
        })
    </script>
    <script>
        let modalBtns = [...document.querySelectorAll(".button")];
        modalBtns.forEach(function(btn) {
            btn.onclick = function() {
                let modal = btn.getAttribute("data-modal");
                document.getElementById(modal).style.display = "block";
            };
        });
        let closeBtns = [...document.querySelectorAll(".close")];
        closeBtns.forEach(function(btn) {
            btn.onclick = function() {
                let modal = btn.closest(".container");
                modal.style.display = "none";
            };
        });
        window.onclick = function(event) {
            if (event.target.className === "container") {
                event.target.style.display = "none";
            }
        };
    </script>
    <!-- partial -->
    <script src="./js/script.js"></script>
    <script src="./js/nav.js"></script>

    <script language="javascript">
        var noPrint = true;
        var noCopy = true;
        var noScreenshot = true;
        var autoBlur = false;

        const actualBtn = document.getElementById('actual-btn');

        const fileChosen = document.getElementById('file-chosen');

        actualBtn.addEventListener('change', function() {
            fileChosen.textContent = this.files[0].name
        });

        const actual_Btn = document.getElementById('actual_btn');

        const file_Chosen = document.getElementById('file_chosen');

        actual_Btn.addEventListener('change', function() {
            file_Chosen.textContent = this.files[0].name
        });
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
        const openPopupBtn = document.getElementById("open-popup");
        const closePopupBtn = document.getElementById("close-popup");
        const popup = document.getElementById("popup");

        openPopupBtn.addEventListener("click", () => {
            popup.style.display = "block";
        });

        closePopupBtn.addEventListener("click", () => {
            popup.style.display = "none";
        });
    </script>
</body>