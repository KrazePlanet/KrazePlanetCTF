<?php
session_start();
require 'db_connection.php';

// Check if the email session variable is set
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];

    // Add products into the cart table
    if (isset($_POST['pid']) && isset($_POST['pname']) && isset($_POST['pprice'])) {
        $pid = $_POST['pid'];
        $pname = $_POST['pname'];
        $pprice = $_POST['pprice'];
        $pimage = $_POST['pimage'];
        $pcode = $_POST['pcode'];
        $pqty = 1;

        $total_price = $pprice * $pqty;

        $stmt = $conn->prepare('SELECT itemName FROM cart WHERE itemName=? AND email=?');
        $stmt->bind_param('ss', $pname, $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $r = $res->fetch_assoc();
        $code = $r['itemName'] ?? '';

        if (!$code) {
            $query = $conn->prepare('INSERT INTO cart (itemName, price, image, quantity, total_price, catName, email) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $query->bind_param('sdsisss', $pname, $pprice, $pimage, $pqty, $total_price, $pcode, $email);
            $query->execute();

            echo '<div class="alert alert-success alert-dismissible mt-2" style="width: 300px; position: fixed; top: 50%; right: 50%; transform: translate(50%, -50%); z-index: 9999; padding-top: 40px; padding-bottom: 40px; font-size: 17px; text-align: center;">
                      <button type="button" class="close" data-dismiss="alert">&times;</button>
                      <strong>Item added to your cart!</strong>
                    </div>';
        } else {
            echo '<div class="alert alert-danger alert-dismissible mt-2" style="width: 300px; position: fixed; top: 50%; right: 50%; transform: translate(50%, -50%); z-index: 9999; padding-top: 40px; padding-bottom: 40px; font-size: 17px; text-align: center;">
                      <button type="button" class="close" data-dismiss="alert">&times;</button>
                      <strong>Item already exists in your cart!</strong>
                    </div>';
        }
    }

    // Get no. of items available in the cart table
    if (isset($_GET['cartItem']) && $_GET['cartItem'] == 'cart_item') {
        $stmt = $conn->prepare('SELECT SUM(quantity) AS qty FROM cart WHERE email=?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Check if 'qty' is null and set to 0 if so
        $quantity = $row['qty'] !== null ? $row['qty'] : 0;

        echo $quantity;
    }

    


    
   
} 
?>
