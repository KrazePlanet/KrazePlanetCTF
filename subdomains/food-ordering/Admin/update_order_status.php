<?php
session_start();
if (!isset($_SESSION['adminloggedin'])) {
    header("Location: ../login.php");
    exit();
}

include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = $_POST['order_id'];
    $orderStatus = $_POST['order_status'];
    $cancelReason = $_POST['cancel_reason'] ?? '';

    if ($orderStatus === 'Cancelled' && empty($cancelReason)) {
        $_SESSION['message'] = "Cancellation reason is required.";
        header("Location: view.php?orderId=" . $orderId);
        exit();
    }

    $updateQuery = "UPDATE orders SET order_status = ?, cancel_reason = ? WHERE order_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('ssi', $orderStatus, $cancelReason, $orderId);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Order status updated successfully.";
    } else {
        $_SESSION['message'] = "Failed to update order status.";
    }

    header("Location: view_order.php?orderId=" . $orderId);
    exit();
} else {
    header("Location: admin_orders.php");
    exit();
}
?>
