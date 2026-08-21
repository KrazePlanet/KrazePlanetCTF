<?php
session_start();
if (!isset($_SESSION['state'])) {
    header('location:bookMe.php');
}
require('../registration/dbcon.php');
?>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- fav-icon -->
    <link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        #customers {
            font-family: Arial, Helvetica, sans-serif;
            border-collapse: collapse;
            width: 80%;
            margin: 2rem;
        }

        #customers td,
        #customers th {
            border: 1px solid #ddd;
            padding: 8px;
        }

        #customers tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        #customers tr:hover {
            background-color: #ddd;
        }

        #customers th {
            padding-top: 12px;
            padding-bottom: 12px;
            text-align: left;
            background-color: rgb(150, 100, 100);
            color: white;
        }

        .pay-btn {
            margin-left: 2rem;
        }
    </style>
    <title>Check Details 💲</title>
</head>

<body>
    <?php
    $email = $_SESSION['email'];
    $query = "select * from payment where emailID='$email' ORDER BY pid DESC LIMIT 1";
    $sql = mysqli_query($conn, $query);
    $data = mysqli_fetch_assoc($sql);
    $uname = $data['uname'];
    $fullAddress = $data['address'];
    $arrayAddress = explode(",", $fullAddress);
    $address = $arrayAddress[0];
    $zip = $arrayAddress[1];
    ?>
    <h1>Details Check</h1>
    <h2>Check Your detail Carefully. After payment you will not be able to change.</h2>
    <table id="customers">
        <tr>
            <th>Info</th>
            <td>Check Your detail last time. After payment you will not be able to change.</td>
        </tr>
        <tr>
            <th>User Name</th>
            <td>
                <?php echo $uname ?>
            </td>
        </tr>
        <tr>
            <th>Email</th>
            <td>
                <?php echo $_SESSION['email'] ?>
            </td>
        </tr>
        <tr>
            <th>Product Name</th>
            <td>
                <?php echo $_SESSION['prod-name'] ?>
            </td>
        </tr>
        <tr>
            <th>Product Amount</th>
            <td>
                <?php echo $_SESSION['amount'] . "₹" ?>
            </td>
        </tr>
        <tr>
            <th>Address</th>
            <td>
                <?php echo $address ?>
            </td>
        </tr>
        <tr>
            <th>Pin code</th>
            <td>
                <?php echo $zip ?>
            </td>
        </tr>
    </table>
    <form method="POST" action="./paymentSubmit.php" class="pay-btn">
        <script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
            data-key="pk_test_51MZqHlBeXDHZA7ZgC2P5VfTl0R0UCCd20oUyMaakp7W1WN75XGBvkn9hATkHyqIENzv95dAard7gKpPBWRqoGIum00cvJWofvG"
            data-name="Picture Perfect" data-description="<?php echo $_SESSION['prod-name'] ?>"
            data-email="<?php echo $_SESSION['email'] ?>"
            data-image="https://store-images.s-microsoft.com/image/apps.46976.13510798882843568.5535f616-656c-43d0-ab96-1a5ad703183c.668151a6-44b0-43d3-9200-f0efbd0aee8e?mode=crop&q=90&h=300&w=300"
            data-label="Pay Now" data-amount="<?php echo $_SESSION['amount'] * 100 ?>" data-currency="inr">
            </script>
    </form>
</body>

</html>