<?php
session_start();
if (!isset($_SESSION['state'])) {
  header('location:bookMe.php');
}
require_once('vendor/autoload.php');
require('../registration/dbcon.php');
\Stripe\Stripe::setApiKey('sk_test_51MZqHlBeXDHZA7ZgFwM2jfM6qW6CyWql3BqZoCuvVKWVtkoCJGoBSTZOmMZwIBJYfx8xgpgbi3YSOVLqLUrxvyQ300rdmSEscg');
$charge = \Stripe\Charge::create([
  'source' => $_POST['stripeToken'],
  'description' => $_SESSION['prod-name'],
  'amount' => $_SESSION['amount'] * 100,
  'currency' => 'inr',
]);
$status = $charge['status'];
$paymentMode = $charge['payment_method'];
$email = $_SESSION['email'];
$updateQuery = "UPDATE payment SET txnStatus='$status'  WHERE emailID='$email'";
$sql = mysqli_query($conn, $updateQuery);
$updateQuery2 = "UPDATE payment SET paymentMode='$paymentMode'  WHERE emailID='$email'";
$sql2 = mysqli_query($conn, $updateQuery2);
$updateQuery3 = "UPDATE registration SET role='2' WHERE email='$email'";
$sql3 = mysqli_query($conn, $updateQuery3);
$_SESSION['role']=2;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- fav-icon -->
  <link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
  <title>Payment Confirmation 🙏🏼</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background-image: url("../../media/images/Header/main-img.jpg");
      background-size: cover;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    /* Glassmorphism card effect */
    .card {
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      background-color: rgba(255, 255, 255, 0.75);
      border-radius: 12px;
      border: 1px solid rgba(209, 213, 219, 0.3);
      color: rgb(43, 40, 41);
      text-align: center;
      width: 500px;
      height: 350px;
      padding: 2rem;
      position: relative;
    }

    .go-back {
      text-decoration: none;
      background-color: rgb(90, 25, 25);
      color: white;
      padding: 1rem 2rem;
      position: absolute;
      top: 70%;
      left: 50%;
      transform: translate(-50%, 0);
    }

    .go-back:hover {
      background-color: rgb(117, 29, 29);
    }
  </style>
</head>

<body>
  <?php
  if ($status == "succeeded") {
    echo '   <div class="card">
  <div class="logo">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 40 40"
      width="100px"
      height="100px"
    >
      <path
        fill="#bae0bd"
        d="M20,38.5C9.8,38.5,1.5,30.2,1.5,20S9.8,1.5,20,1.5S38.5,9.8,38.5,20S30.2,38.5,20,38.5z"
      />
      <path
        fill="#5e9c76"
        d="M20,2c9.9,0,18,8.1,18,18s-8.1,18-18,18S2,29.9,2,20S10.1,2,20,2 M20,1C9.5,1,1,9.5,1,20s8.5,19,19,19	s19-8.5,19-19S30.5,1,20,1L20,1z"
      />
      <path
        fill="none"
        stroke="#fff"
        stroke-miterlimit="10"
        stroke-width="3"
        d="M11.2,20.1l5.8,5.8l13.2-13.2"
      />
    </svg>
  </div>
  <div class="message">
    <h1>Payment Success !</h1>
    <p>
    We will contact you soon.
    </p>
    <p>
    Have a nice day.
    </p>
    <a class="go-back" href="../../index.php">Go to Home</a>
  </div>
</div>';
  } else {
    echo '<div class="card">
  <div class="logo">
    <svg
      xmlns="http://www.w3.org/2000/svg"
      viewBox="0 0 48 48"
      width="100px"
      height="100px"
    >
      <linearGradient
        id="wRKXFJsqHCxLE9yyOYHkza"
        x1="9.858"
        x2="38.142"
        y1="9.858"
        y2="38.142"
        gradientUnits="userSpaceOnUse"
      >
        <stop offset="0" stop-color="#f44f5a" />
        <stop offset=".443" stop-color="#ee3d4a" />
        <stop offset="1" stop-color="#e52030" />
      </linearGradient>
      <path
        fill="url(#wRKXFJsqHCxLE9yyOYHkza)"
        d="M44,24c0,11.045-8.955,20-20,20S4,35.045,4,24S12.955,4,24,4S44,12.955,44,24z"
      />
      <path
        d="M33.192,28.95L28.243,24l4.95-4.95c0.781-0.781,0.781-2.047,0-2.828l-1.414-1.414	c-0.781-0.781-2.047-0.781-2.828,0L24,19.757l-4.95-4.95c-0.781-0.781-2.047-0.781-2.828,0l-1.414,1.414	c-0.781,0.781-0.781,2.047,0,2.828l4.95,4.95l-4.95,4.95c-0.781,0.781-0.781,2.047,0,2.828l1.414,1.414	c0.781,0.781,2.047,0.781,2.828,0l4.95-4.95l4.95,4.95c0.781,0.781,2.047,0.781,2.828,0l1.414-1.414	C33.973,30.997,33.973,29.731,33.192,28.95z"
        opacity=".05"
      />
      <path
        d="M32.839,29.303L27.536,24l5.303-5.303c0.586-0.586,0.586-1.536,0-2.121l-1.414-1.414	c-0.586-0.586-1.536-0.586-2.121,0L24,20.464l-5.303-5.303c-0.586-0.586-1.536-0.586-2.121,0l-1.414,1.414	c-0.586,0.586-0.586,1.536,0,2.121L20.464,24l-5.303,5.303c-0.586,0.586-0.586,1.536,0,2.121l1.414,1.414	c0.586,0.586,1.536,0.586,2.121,0L24,27.536l5.303,5.303c0.586,0.586,1.536,0.586,2.121,0l1.414-1.414	C33.425,30.839,33.425,29.889,32.839,29.303z"
        opacity=".07"
      />
      <path
        fill="#fff"
        d="M31.071,15.515l1.414,1.414c0.391,0.391,0.391,1.024,0,1.414L18.343,32.485	c-0.391,0.391-1.024,0.391-1.414,0l-1.414-1.414c-0.391-0.391-0.391-1.024,0-1.414l14.142-14.142	C30.047,15.124,30.681,15.124,31.071,15.515z"
      />
      <path
        fill="#fff"
        d="M32.485,31.071l-1.414,1.414c-0.391,0.391-1.024,0.391-1.414,0L15.515,18.343	c-0.391-0.391-0.391-1.024,0-1.414l1.414-1.414c0.391-0.391,1.024-0.391,1.414,0l14.142,14.142	C32.876,30.047,32.876,30.681,32.485,31.071z"
      />
    </svg>
  </div>
  <div class="message">
    <h1>Payment Failed !</h1>
    <p>Sorry For trouble pls try again after sometime.</p>
    <p>Have a nice day.</p>
    <a class="go-back" href="../../index.php">Go to Home</a>
  </div>
</div>';
  }
  ?>


</body>

</html>