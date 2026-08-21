<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
    $user_id = $_SESSION['user_id'];
    }else{
    $user_id = '';
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing table</title>
        <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- custom css file link  -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/pricing.css">

            <!-- header section starts  -->
            <?php include 'components/user_header.php'; ?>
</head>
<body>

<div class="heading">
    <h2 style="font-size: 50px; color:white;">Get Started Now</h2>
    <h4 style="font-size: 25px; color:white;">Pick a plan</h4>
    <br>
    <br>
    <p><a href="home.php">home</a> <span> / Pricing</span></p>
</div>

<div class="pricing-table-container">
    <div class="pricing-header">
        <h2>Please choose a plan below</h2>
        <div class="plans-switch-container">
            <input type="checkbox" class="plans-switch">
            <span class="monthly">Monthly</span>
            <span class="yearly">Yearly</span>
        </div>
    </div>

    <div class="pricing-table">
        <div class="table">
            <div class="content">
                <h3>Basic</h3>
                <div class="price-container">
                    <span class="price basic-price">$49</span>
                    <span class="plan-duration" style="font-size:13px;">/ month</span>
                </div>
                <div class="description">
                    This plan is the best for individuals who are getting started
                </div>
                <ul class="features">
                    <li>Wifi</li>
                    <li>Coffee/Tea</li>
                    <li>Membership</li>
                    <li>Meeting Room</li>
                </ul>
                <a href="payment.php?plan=Basic" class="btn">Choose Plan</a>
            </div>
            <img class="table-bg" src="img/bg-shape1.svg" alt="">
        </div>
        <!-- End of Basic plan -->

        <div class="table best-value">
            <span class="value">Best Value</span>
            <div class="content">
                <h3 style="color:#CF9D63; font-weight:bold;">Professional</h3>
                <div class="price-container">
                    <span class="price professional-price">$99</span>
                    <span class="plan-duration" style="font-size:13px;">/ month</span>
                </div>
                <div class="description">
                    This plan is for businesses that are getting started
                </div>
                <ul class="features">
                    <li>Wifi High Speed</li>
                    <li>Coffee/Tea</li>
                    <li>Printer</li>
                    <li>Membership</li>
                    <li>Office Space</li>
                    <li>Meeting Room</li>
                    <li>Dedicated Desk</li>
                </ul>
                <a href="payment.php?plan=Professional" class="btn">Choose Plan</a>
            </div>
            <img class="table-bg" src="img/bg-shape2.svg" alt="">
        </div>
        <!-- End of Professional Plan -->

        <div class="table">
            <div class="content">
                <h3>Business</h3>
                <div class="price-container">
                    <span class="price business-price">$149</span>
                    <span class="plan-duration" style="font-size:13px;">/ month</span>
                </div>
                <div class="description">
                    This plan is the best for large businesses
                </div>
                <ul class="features">
                    <li>Wifi High Speed</li>
                    <li>Coffee/Tea</li>
                    <li>Printer</li>
                    <li>Membership</li>
                    <li>Office Space</li>
                    <li>Meeting Room</li>
                    <li>Dedicated Desk</li>
                    <li>Virtual Office</li>
                </ul>
                <a href="payment.php?plan=Business" class="btn">Choose Plan</a>
            </div>
            <img class="table-bg" src="img/bg-shape1.svg" alt="">
        </div>
    </div>
</div>


            <!-- footer section starts  -->
            <?php include 'components/footer.php'; ?>
        <!-- footer section ends -->
        <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>

        <!-- custom js file link  -->
        <script src="js/script.js"></script>
        <script src="js/pricing.js"></script>
        
</body>
</html>