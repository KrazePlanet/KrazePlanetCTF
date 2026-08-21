<?php
session_start();

if (!isset($_SESSION['amount'])) {
    header('location:bookMe.php');
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../css/payment.css" />
    <!-- fav-icon -->
    <link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
    <script src="../../js/payment.js" defer></script>
    <title>
        <?php echo $_SESSION['prod-name'] . "💲" ?>
    </title>
</head>

<body>
    <?php
    require('../registration/dbcon.php');
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $uname = $_POST['uname'];
        $email = $_SESSION['email'];
        $state = $_POST['state'];
        $city = $_POST['city'];
        $address = $_POST['address'];
        $_SESSION['state'] = $state;
        $zip = $_POST['zip'];
        $amount = $_SESSION['amount'];
        $query = "INSERT INTO payment (emailID, amount, paymentMode ,txnStatus,address, uname) VALUES ('$email','$amount','pending','pending','$address $city $state,$zip','$uname')";
        $sql = mysqli_query($conn, $query);
        if (!$sql) {
            new Error("Error occured");
        }
        $dataAdded = "select * from payment where emailID='$email'";
        $sql2 = mysqli_query($conn, $dataAdded);
        $dataAdded = mysqli_num_rows($sql2);
        if ($dataAdded) {
            header('location:paymentCheck.php');
        } else {
            $problem = "some error occured try again";
        }
    }
    ?>
    <h2>Checkout Form</h2>
    <p>Note:-Pls select your State to get list of available cities. If you cant find your city we are not currently available in your location.</p>
    <p>you can only see the email its non changable</p>
    <p>
        <?php if (isset($problem)) {
            echo $problem;
        } ?>
    </p>
    <p>fill the form carefully.</p>
    <div class="row">
        <div class="col-75">
            <div class="container">
                <form method="POST" action="<?php echo htmlentities($_SERVER['PHP_SELF']) ?>">
                    <div class="row">
                        <div class="col-50">
                            <h3>Billing</h3>
                            <label for="fname"><i class="fa fa-user"></i> Name</label>
                            <input type="text" id="uname" name="uname" autocomplete="off" placeholder="Your Name"
                                value="<?php echo isset($_POST["uname"]) ? $_POST["uname"] : $_SESSION['uname']; ?>"
                                required>
                            <label for="email"><i class="fa fa-envelope"></i> Email</label>
                            <input type="email" id="email" name="email" autocomplete="off" placeholder="Your email id"
                                value="<?php echo $_SESSION['email'] ?>" disabled>
                            <div class="row">
                                <div class="col-50">
                                    <label for="state">State</label>
                                    <select name="state" id="state"
                                        value="<?php echo isset($_POST["state"]) ? $_POST["state"] : ""; ?>">
                                        <option value="state">select State</option>
                                        <option value="Gujrat">Gujrat</option>
                                        <option value="Haryana">Haryana</option>
                                        <option value="Punjab">Punjab</option>
                                        <option value="Rajasthan">Rajasthan</option>
                                    </select>
                                </div>
                                <div class="col-50">
                                    <label for="city"
                                        value="<?php echo isset($_POST["city"]) ? $_POST["city"] : ""; ?>"><i
                                            class="fa fa-institution"></i> City</label>
                                    <select name="city" id="city" required>
                                    </select>
                                </div>
                                <div class="col-50">
                                    <label for="adr"><i class="fa fa-address-card-o"></i> Address</label>
                                    <input type="text" id="adr" name="address"
                                        value="<?php echo isset($_POST["address"]) ? $_POST["address"] : ""; ?>"
                                        autocomplete="off" placeholder="T550 Near ABC Gate " required>
                                </div>
                                <div class="col-50">
                                    <label for="zip">Zip</label>
                                    <input type="text" id="zip"
                                        value="<?php echo isset($_POST["zip"]) ? $_POST["zip"] : ""; ?>" name="zip"
                                        autocomplete="off" placeholder="342001" required>

                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="submit" name="submit" value="Continue to checkout" class="btn">
                </form>
            </div>
        </div>
        <div class="col-25">
            <div class="container">
                <p>Product-Name:-</p>
                <p class="price"><b>
                        <?php echo $_SESSION['prod-name'] ?>
                    </b></p>
                <hr>
                <p>Total <span class="price"><b>
                            <?php echo $_SESSION['amount'] . "₹" ?>
                        </b></span></p>
            </div>
        </div>
    </div>
    <!-- footer section of home page -->
    <footer>
        <div>
            <img class="logo" src="../../media/images/Header/logo.png" alt="picture perfect logo" />
        </div>
        <div class="footer-content">
            &copy; Copyright 2022-2023 by Refsnes Data. All Rights Reserved. Picture
            Perfect is Powered by Perfect.corp.
        </div>
    </footer>
</body>

</html>