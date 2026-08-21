<?php

include 'config.php';
require 'front-controller.php';

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
$id = $_POST['id'];
$check_file_sql = "SELECT * FROM `whale_enterprises`.`software_model` WHERE id = '$id'";
$check_file_result = mysqli_query($link, $check_file_sql);
while ($row = mysqli_fetch_assoc($check_file_result)) {
    $pdf = $row['file'];
}
$path = "./uploads/";
$file = $path . $pdf;
$sector_filter = $_POST['sector'];
?>

<!DOCTYPE html>
<html lang="en">
<script>
    window.console = window.console || function(t) {};
</script>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Whale Enterprises Pvt Ltd</title>
    <link rel="stylesheet" href="./css/style.css">
    <link rel="icon" type="image/x-icon" href="./assets/logo.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
    <script>
        alert("Do not take screenshot of this page");
    </script>
</head>

<style>
    body {
        background-color: #525659;
        color: #fff;
    }

    /* We are stopping user from
         printing our webpage */
    @media print {

        html,
        body {
            display: none !important;
        }
    }

    html {
        user-select: none;
    }

    #element {
        display: block;
        max-width: 93vw;
        overflow-x: hidden;
    }

    #canvas_container {
        text-align: center;
        margin-top: 2vh;
        transform: scale(.9);
    }

    .pdf-controls {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
    }

    .pdf-controls button {
        width: fit-content;
        padding: 5px;
        height: 30px;
        background-color: #fff;
        color: #000;
        margin: 5px;
        border-color: 0px;
        border-radius: 5px;
        cursor: pointer;
    }

    body {
        position: relative;

    }

    body::after {
        content: "<?php echo $username ?>";
        font-size: 4em;
        font-weight: bold;
        color: #000;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        pointer-events: none;
        z-index: 2;
        opacity: 0.2;
    }

    @media screen and (max-width: 960px) {
        body::after {
            top: 65%;
            font-size: 2em;
        }
    }

    #pdf_renderer {
        max-width: fit-content;
        height: fit-content;
        display: block;
        margin: 0 auto;
    }

    .align_bottom {
        position: fixed;
        bottom: 0%;
        padding-bottom: 20px;
        left: 0;
        width: 100%;
        height: 50px;
        text-align: center;
        font-size: 16px;
        padding-top: 7px;
    }

    a {
        text-decoration: none;
        color: #000;
    }
</style>

<body class="noprint" oncontextmenu="return false;">
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
                <li><a href="view-only.php" style="color:#fff" class="active">Documents</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php" class="join-button">Logout</a></li>
            </ul>
        </nav>
    <?php } ?>
    <div id="element" class="view-pdf-overall">
        <?php
        $sql = "SELECT * FROM software_model WHERE id = '$id'";
        $result = mysqli_query($link, $sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
        ?>
                <div style="padding-top:10vh;" class="flex-row column">
                    <div class="flex-row">
                        <p>Drawing Number: &nbsp;</p>
                        <p class="view-pdf-overall-db-content"><?php echo $row['drawing_number'] ?> &nbsp; &nbsp; &nbsp; &nbsp;</p>
                    </div>
                    <div class="flex-row">
                        <p>Revision Number: &nbsp; </p>
                        <p class="view-pdf-overall-db-content"><?php echo $row['revision_number'] ?> &nbsp; &nbsp; &nbsp; &nbsp;</p>
                    </div>
                    <div class="flex-row">
                        <p>Sector: &nbsp; </p>
                        <p class="view-pdf-overall-db-content"><?php echo $row['sector'] ?> &nbsp; &nbsp; &nbsp; &nbsp;</p>
                    </div>
                </div>
                <div class="flex-row">
                    <p>Description: &nbsp; </p>
                    <p class="view-pdf-overall-db-content"><?php echo $row['description'] ?></p>
                </div>
                <?php if (trim($admin) == 'SuperAdmin' or trim($admin) == 'Admin') { ?>
                    <div class="flex-row">
                        <p>Inserted By: &nbsp; </p>
                        <p class="view-pdf-overall-db-content"><?php echo $row['inserted_user'] ?></p>
                    </div>
                    <!--<p class="view-pdf-overall-db-content"><?php # echo $pdf 
                                                                ?></p>-->
        <?php }
            }
        } ?>
        <div class="container">
            <div id="my_pdf_viewer">
                <div id="canvas_container">
                    <canvas id="pdf_canvas"></canvas>
                </div>
            </div>
            <div class="align_bottom">
                <div class="pdf-controls" id="navigation_controls">
                    <button id="prev">Previous</button>
                    <button id="next">Next</button>
                    <button id="zoomIn">++</button>
                    <button id="zoomOut">--</button>
                    <button id="rotateClockwise">Rotate 90° Clockwise</button>
                    <button id="rotateCounterClockwise">Rotate 90° Counterclockwise</button>
                    <button>Page: <span id="page_num"></span> / <span id="page_count"></span></button>
                    <button class="back_button"><a href="view-only.php">Go Back</a></button>
                    <input id="current_page" value="1" type="hidden" />
                </div>
            </div>
        </div>
    </div>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.worker.min.js';

        var pdfDoc = null,
            pageNum = 1,
            pageRendering = false,
            pageNumPending = null,
            scale = 1.5,
            rotation = 0,
            canvas = document.getElementById("pdf_canvas"),
            ctx = canvas.getContext('2d');

        function renderPage(num) {
            pageRendering = true;
            pdfDoc.getPage(num).then((page) => {
                var viewport = page.getViewport({
                    scale,
                    rotation
                });
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                var renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                var renderTask = page.render(renderContext);
                renderTask.promise.then(() => {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });
            document.getElementById('page_num').textContent = num;
        }

        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function onPrevPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
        }
        document.getElementById('prev').addEventListener('click', onPrevPage);

        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            queueRenderPage(pageNum);
        }
        document.getElementById('next').addEventListener('click', onNextPage);

        function zoomOut() {
            scale -= 0.1;
            renderPage(pageNum);
        }
        document.getElementById('zoomOut').addEventListener('click', zoomOut);

        function zoomIn() {
            scale += 0.1;
            renderPage(pageNum);
        }
        document.getElementById('zoomIn').addEventListener('click', zoomIn);

        function rotateClockwise() {
            rotation += 90;
            renderPage(pageNum);
        }
        document.getElementById('rotateClockwise').addEventListener('click', rotateClockwise);

        function rotateCounterClockwise() {
            rotation -= 90;
            renderPage(pageNum);
        }
        document.getElementById('rotateCounterClockwise').addEventListener('click', rotateCounterClockwise);

        pdfjsLib.getDocument('<?php echo $file ?>').promise.then((doc) => {
            pdfDoc = doc;
            document.getElementById('page_count').textContent = doc.numPages;
            renderPage(pageNum);
        });
    </script>

    <!--Security-->

    <script language="javascript">
        var noPrint = true;
        var noCopy = true;
        var noScreenshot = true;
        var autoBlur = false;
        document.onkeydown = function(e) {
            if (e.key === "Windows") {
                e.preventDefault();
            }
        };
        document.onkeydown = function(e) {
            if (e.key === "PrintScreen") {
                e.preventDefault();
            }
        };
        document.addEventListener("keydown", function(event) {
            if (event.key === "Meta" || event.key === "Win") {
                event.preventDefault();
            }
        });
        document.addEventListener("keydown", function(event) {
            if (event.key === "PrintScreen") {
                event.preventDefault();
            }
        });
        document.addEventListener("keydown", function() {
            document.getElementById("element").style.display = "none";
        });
        document.addEventListener("click", function() {
            document.getElementById("element").style.display = "block";
        });

        function stopPrntScr() {
            var inpFld = document.createElement("input");
            inpFld.setAttribute("value", ".");
            inpFld.setAttribute("width", "0");
            inpFld.style.height = "0px";
            inpFld.style.width = "0px";
            inpFld.style.border = "0px";
            document.body.appendChild(inpFld);
            inpFld.select();
            document.execCommand("copy");
            inpFld.remove(inpFld);
        }

        function AccessClipboardData() {
            try {
                window.clipboardData.setData('text', "Access   Restricted");
            } catch (err) {}
        }
        stopPrntScr()
        AccessClipboardData()

        //blur

        // Get a reference to the element with the ID "element"
        const element = document.getElementById('element');

        // Add an event listener to the element to detect when the mouse leaves the element
        element.addEventListener('mouseleave', () => {
            // Blur the element's content
            element.style.filter = 'blur(12px)';
        });
        document.body.addEventListener('click', () => {
            // Remove the blur effect from the element's content
            element.style.filter = 'none';
        });
        // Get the element where you want to disable long press

        // Set the minimum duration of the key press in milliseconds
        var longPressDuration = 1000;

        // Create a variable to store the start time of the key press
        var pressStartTime;

        // Add a keydown event listener to the element
        element.addEventListener("keydown", function(event) {
            // Get the current time
            var currentTime = new Date().getTime();

            // If this is the first keydown event or the key has been released and pressed again
            if (!pressStartTime || (currentTime - pressStartTime) > longPressDuration) {
                // Set the press start time to the current time
                pressStartTime = currentTime;
            }
            // If the key has been held down for more than the long press duration
            else if ((currentTime - pressStartTime) > longPressDuration) {
                // Prevent the default behavior of the event
                event.preventDefault();
            }
        });
        let isKeyDown = false;

        document.addEventListener('keydown', function(event) {
            if (event.keyCode === 44) { // Print Screen key
                event.preventDefault();
                isKeyDown = true;
            }
        });

        document.addEventListener('keyup', function(event) {
            if (event.keyCode === 44) { // Print Screen key
                if (isKeyDown) {
                    // Hide content inside the div
                    const contentDiv = document.getElementById('my_pdf_viewer');
                    contentDiv.style.display = 'none';
                }
                isKeyDown = false;
            }
        });

        var timeoutId;

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                timeoutId = setTimeout(function() {
                    element.style.filter = 'blur(12px)';
                }, 1000); // Set a timeout of 1 second (1000 milliseconds)
            }
        });

        document.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                clearTimeout(timeoutId);
            }
        });



        //checking

        const contentDiv = document.getElementById('my_pdf_viewer');

        document.addEventListener('keydown', (e) => {
            contentDiv.classList.add('hidden');
            navigator.clipboard.writeText('');
            console.log("Print Screen key pressed");

        });
        document.addEventListener("keydown", function(event) {
            if (event.key === "PrintScreen" || event.key === "Prt Sc") {
                // Do something when Print Screen is pressed
                console.log("Print Screen key pressed");
                event.preventDefault(); // Prevent the default browser behavior
            }
        });
    </script>
    <p id="test"></p>

    <script type="text/javascript">
        $(document).ready(function() {
            $(window).keyup(function(e) {
                if (e.keyCode == 44) {
                    $("body").hide();
                }

            });
        });


        $(window).focus(function() {
            $("body").show();
        }).blur(function() {
            $("body").hide();
        });

        $(document).keyup(function(e) {
            if (e.keyCode == 44) return false;
        });

        function copyToClipboard(elementId) {
            // Create a "hidden" input
            var aux = document.createElement("input");

            // Assign it the value of the specified element
            aux.setAttribute("value", document.getElementById(elementId).innerHTML);

            // Append it to the body
            document.body.appendChild(aux);

            // Highlight its content
            aux.select();

            // Copy the highlighted text
            document.execCommand("copy");

            // Remove it from the body
            document.body.removeChild(aux);

        }
        $(document).ready(function() {
            $(window).keyup(function(e) {
                if (e.keyCode == 44) {
                    copyToClipboard('test');
                };
            });
        });
        document.addEventListener("keyup", function(e) {
            var keyCode = e.keyCode ? e.keyCode : e.which;
            if (keyCode == 44) {
                stopPrntScr();
            }
        });

        function stopPrntScr() {

            var inpFld = document.createElement("input");
            inpFld.setAttribute("value", ".");
            inpFld.setAttribute("width", "0");
            inpFld.style.height = "0px";
            inpFld.style.width = "0px";
            inpFld.style.border = "0px";
            document.body.appendChild(inpFld);
            inpFld.select();
            document.execCommand("copy");
            inpFld.remove(inpFld);
        }

        function AccessClipboardData() {
            try {
                window.clipboardData.setData('text', "Access   Restricted");
            } catch (err) {}
        }
        setInterval("AccessClipboardData()", 1);
    </script>

    <script src="./js/nav.js"></script>
    <script type="text/javascript" src="https://pdfanticopy.com/noprint.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.11.338/pdf.min.js"></script>
</body>

</html>