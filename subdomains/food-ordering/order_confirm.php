<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userloggedin']) || $_SESSION['userloggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Get the order_id from the URL parameter
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Fetch order details
$stmt = $conn->prepare('SELECT * FROM orders WHERE order_id=?');
if ($stmt === false) {
    die('Failed to prepare order details statement: ' . $conn->error);
}
$stmt->bind_param('i', $orderId);
$stmt->execute();
$orderResult = $stmt->get_result();
$order = $orderResult->fetch_assoc();

if ($order === null) {
    die('Order not found.');
}

// Fetch order items
$stmt = $conn->prepare('SELECT * FROM order_items WHERE order_id=?');
if ($stmt === false) {
    die('Failed to prepare order items statement: ' . $conn->error);
}
$stmt->bind_param('i', $orderId);
$stmt->execute();
$orderItemsResult = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css' />
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css' />
    <!--Bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Order Confirmation</title>
    <style>
        body {
            padding-top: 120px;
            padding-bottom: 50px;
        }

        .card h1,
        .card p {
            animation: translate-y-100 300ms cubic-bezier(0.165, 0.84, 0.44, 1.2) forwards;
            opacity: 0;
        }

        .card h3 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            animation-delay: 1100ms;
        }

        .card p {
            margin-top: 0;
            font-size: 17px;
            color: #60655f;
            animation-delay: 1150ms;
        }

        .card .cta-row {
            display: flex;
            justify-content: center;
            gap: 12px;
            width: 100%;
            animation: translate-y-100 600ms 2200ms cubic-bezier(0.19, 1, 0.22, 1) forwards;
            opacity: 0;
        }

        .card a button {
            position: relative;
            height: 36px;
            width: 200px;
            border: 0;
            border-radius: 5px;
            line-height: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #46a758;
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.2) inset,
                0 1px 3px 0 rgba(24, 25, 22, 0.1), 0 1px 2px -1px rgba(24, 25, 22, 0.1);
            border: 1px solid #2a7e3b;
            color: #ffffff;
            font-size: 15px;
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: transform 200ms cubic-bezier(0.165, -0.84, 0.44, 2),
                opacity 200ms ease, background-color 100ms ease;
            cursor: pointer;
        }

        .card a button:hover {
            background: #2a7e3b;
        }

        .card a button:active {
            transform: scale(0.98);
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04) inset;
        }

        .card a button:active:before {
            opacity: 0.4;
        }

        .card a button.secondary {
            color: #fff;
            background: #fb4a36;
            box-shadow: var(--box-shadow);
            border: 1px solid #fb4a36;
            transition: all 0.3s ease;
        }

        .card a button.secondary:hover {
            background: none;
            color: #fb4a36;
        }

        .card a button.secondary:active {
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04) inset;
        }

        .card {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 448px;
            padding: 32px;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .card:before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.01), 0 2px 4px rgba(0, 0, 0, 0.04);
            border-radius: 5px;
            clip-path: inset(0px -4px 0px -4px);
        }

        .card:nth-of-type(3):before {
            clip-path: inset(0px -4px -4px -4px);
        }

        .card:first-of-type {
            animation: translate-y-100 500ms 1000ms cubic-bezier(0.19, 1, 0.22, 1.2) forwards;
            opacity: 0;
            z-index: 0;
        }

        .card .icon {
            width: 64px;
            height: 64px;
            border: 2px solid #ffffff;
            border-radius: 9999px;
            background: linear-gradient(to top, #f2f2f280, #e0e0e080);
            box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.06) inset;
            display: grid;
            place-content: center;
            margin-bottom: 1rem;
        }

        .card .icon:before {
            content: "\2713";
            display: grid;
            place-items: center;
            width: 56px;
            height: 56px;
            border-radius: 9999px;
            background-color: #ffffff;
            box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.03);
            font-family: "arial";
            font-size: 32px;
            color: #46a758;
        }

        .card:not(:first-of-type) {
            transform-origin: top;
            animation: unfold 500ms 1700ms cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
            -webkit-transform: perspective(500px) rotateX(-0.25turn);
            z-index: 1;
            opacity: 0.9;
        }

        .card:nth-of-type(3) {
            animation-delay: 1950ms;
            animation-timing-function: cubic-bezier(0.25, 0.46, 0.45, 1.4);
            z-index: 2;
        }

        .card:not(:first-of-type):after {
            position: absolute;
            top: -0.5px;
            left: 1%;
            content: "";
            width: 98%;
            height: 1px;
            border-top: 1px #d7dad7 dashed;
        }

        .card ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .card ul>li {
            width: 100%;
            display: flex;
            justify-content: space-between;
        }

        .card ul>li span {
            font-size: 16px;
            font-weight: 500;
            color: #212121;
        }

        .card ul>li span:first-of-type {
            color: black;
        }

        @keyframes translate-y-100 {
            0% {
                transform: scale(0.9) translateY(0.5rem);
            }

            100% {
                opacity: 1;
            }
        }

        @keyframes unfold {
            to {
                transform: none;
                opacity: 1;
            }
        }

        #wrapper {
            margin-bottom: 50px;
        }

        .card a {
            text-decoration: none;
        }

        .card a:hover {
            text-decoration: none;
        }
        @media screen and (max-width: 500px) {
            .card {
               width: 350px !important;
               padding: 32px 20px !important;
               margin: 0 20px 0 20px !important;
            }
            .card ul>li span {
                font-size: 14px !important;
            }
           
            .card a button{
                width: 140px;
            }
        }
        
    </style>
</head>

<body>
    <?php include('nav-logged.php'); ?>
    <div class="title d-flex justify-content-center align-items-center">
        <div id="wrapper">
            <div class="card" style="background: rgba(255, 99, 132, 0.3);">
                <div class="icon"></div>
                <h3>Thank You for Your Order!</h3>
                <p>Your order has been successfully placed.</p>
            </div>
            <div class="card" style="background: rgba(255, 99, 132, 0.3);">
                <ul>
                    <li>
                        <span><strong>Order ID:</strong></span>
                        <span>#<?= htmlspecialchars($order['order_id'] ?? 'N/A') ?></span>
                    </li>
                    <li>
                        <span><strong>Email:</strong></span>
                        <span><?= htmlspecialchars($order['email'] ?? 'N/A') ?></span>
                    </li>
                    <li>
                        <span><strong>Payment Method:</strong></span>
                        <span><?= htmlspecialchars($order['pmode'] ?? 'N/A') ?></span>
                    </li>
                    <li>
                        <span><strong>Order Items:</strong></span>
                        <span>
                            <ul>
                                <?php while ($item = $orderItemsResult->fetch_assoc()) : ?>
                                    <li><?= htmlspecialchars($item['itemName']) ?> - <?= htmlspecialchars($item['quantity']) ?></li>
                                <?php endwhile; ?>
                            </ul>
                        </span>
                    </li>
                    <li>
                        <span><strong>Total:</strong></span>
                        <span>Rs <?= number_format($order['grand_total']) ?></span>
                    </li>
                    <li>
                        <span><strong>Address:</strong></span>
                        <span><?= htmlspecialchars($order['address'] ?? 'N/A') ?></span>
                    </li>
                </ul>
            </div>
            <div class="card" style="background: rgba(255, 99, 132, 0.3);">
                <div class="cta-row">
                    <a href="menu.php">
                        <button class="secondary">
                            Back To Menu
                        </button>
                    </a>
                    <a href="orders.php">
                        <button>
                            Track Order
                        </button>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php
include_once ('footer.html');
?>

<script>
    $(document).ready(function() {
      console.log('Page is ready. Calling load_cart_item_number.');
      load_cart_item_number();

      function load_cart_item_number() {
        $.ajax({
          url: 'action.php',
          method: 'get',
          data: {
            cartItem: "cart_item"
          },
          success: function(response) {
            $("#cart-item").html(response);
          }
        });
      }
    });
  </script>
</body>

</html>