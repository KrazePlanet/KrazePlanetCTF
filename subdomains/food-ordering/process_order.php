<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userloggedin']) || $_SESSION['userloggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Retrieve form data
$firstName = $_POST['firstName'] ?? '';
$lastName = $_POST['lastName'] ?? '';
$email = $_POST['email'] ?? '';
$address = $_POST['address'] ?? '';
$contact = $_POST['contact'] ?? '';
$orderNote = $_POST['order_note'] ?? '';
$paymentMode = $_POST['payment_mode'] ?? '';
$total = $_POST['total'] ?? 0;
$subtotal = $_POST['subtotal'] ?? 0;
$selectedItems = json_decode($_POST['selected_items'], true) ?? [];

// Ensure the payment mode is not "card"
if ($paymentMode === 'card') {
    header('Location: order_review.php');
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Insert order details
    $stmt = $conn->prepare('INSERT INTO orders (firstName, lastName, email, phone, address, sub_total, grand_total, pmode, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if ($stmt === false) {
        throw new Exception('Failed to prepare order insertion statement: ' . $conn->error);
    }
    $stmt->bind_param('sssssddss', $firstName, $lastName, $email, $contact, $address, $subtotal, $total, $paymentMode, $orderNote);
    $stmt->execute();
    $orderId = $stmt->insert_id;

    // Prepare statement for inserting order items
    $stmt = $conn->prepare('INSERT INTO order_items (order_id, itemName, quantity, price, total_price, image) VALUES (?, ?, ?, ?, ?, ?)');
    if ($stmt === false) {
        throw new Exception('Failed to prepare order items insertion statement: ' . $conn->error);
    }
    
    foreach ($selectedItems as $item) {
        $itemId = $item['id'] ?? 0;
        $itemQuantity = $item['quantity'] ?? 0;

        // Fetch item details from the cart
        $itemStmt = $conn->prepare('SELECT * FROM cart WHERE id=? AND email=?');
        $itemStmt->bind_param('is', $itemId, $email);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();
        $itemDetails = $itemResult->fetch_assoc();

        if ($itemDetails === null) {
            throw new Exception('Item not found in cart.');
        }

        $itemName = $itemDetails['itemName'];
        $itemPrice = $itemDetails['price'];
        $totalPrice = $itemPrice * $itemQuantity;
        $itemImage = $itemDetails['image'];

        $stmt->bind_param('issdds', $orderId, $itemName, $itemQuantity, $itemPrice, $totalPrice, $itemImage);
        $stmt->execute();

        // Remove each item from the cart
        $deleteStmt = $conn->prepare('DELETE FROM cart WHERE id=? AND email=?');
        $deleteStmt->bind_param('is', $itemId, $email);
        $deleteStmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Redirect to confirmation page with the order ID
    header('Location: order_confirm.php?order_id=' . $orderId);
    exit;

} catch (Exception $e) {
    // Rollback transaction in case of error
    $conn->rollback();
    echo 'Error: ' . $e->getMessage();
}
?>
