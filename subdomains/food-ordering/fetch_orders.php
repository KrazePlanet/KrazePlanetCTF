<?php
session_start();
include 'db_connection.php'; // Ensure you have a db_connection.php file to connect to your database

$email = $_SESSION['email'];
$orderStatus = isset($_GET['status']) ? $_GET['status'] : 'All';

// Modify the query to include review information using a LEFT JOIN
$query = "SELECT orders.*, reviews.review_text, reviews.response 
          FROM orders 
          LEFT JOIN reviews ON orders.order_id = reviews.order_id 
          WHERE orders.email = ?";
if ($orderStatus !== 'All') {
    $query .= " AND orders.order_status = ?";
}

$stmt = $conn->prepare($query);
if ($orderStatus === 'All') {
    $stmt->bind_param('s', $email);
} else {
    $stmt->bind_param('ss', $email, $orderStatus);
}

$stmt->execute();
$result = $stmt->get_result();
$orders = [];

while ($order = $result->fetch_assoc()) {
    $orderId = $order['order_id'];
    
    // Fetch the order items
    $itemsQuery = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsQuery->bind_param('i', $orderId);
    $itemsQuery->execute();
    $itemsResult = $itemsQuery->get_result();
    $order['items'] = $itemsResult->fetch_all(MYSQLI_ASSOC);
    $itemsQuery->close();

    // Include the cancellation reason if the order is cancelled
    if ($order['order_status'] === 'Cancelled') {
        $cancelQuery = $conn->prepare("SELECT cancel_reason FROM orders WHERE order_id = ?");
        $cancelQuery->bind_param('i', $orderId);
        $cancelQuery->execute();
        $cancelResult = $cancelQuery->get_result();
        $cancelData = $cancelResult->fetch_assoc();
        $order['cancel_reason'] = $cancelData['cancel_reason'];
        $cancelQuery->close();
    }

    // Review information is already included in the main query, no need for extra fetch here
    $orders[] = $order;
}

echo json_encode($orders);

$stmt->close();
$conn->close();
?>
