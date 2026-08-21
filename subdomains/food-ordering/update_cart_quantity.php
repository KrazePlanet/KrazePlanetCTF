<?php
session_start();
require 'db_connection.php';

if (isset($_POST['id']) && isset($_POST['quantity']) && isset($_POST['total_price'])) {
    $id = $_POST['id'];
    $quantity = $_POST['quantity'];
    $total_price = $_POST['total_price'];

    // Update quantity and total price in the cart
    $stmt = $conn->prepare('UPDATE cart SET quantity=?, total_price=? WHERE id=?');
    $stmt->bind_param('idi', $quantity, $total_price, $id);
    $stmt->execute();
}
?>
